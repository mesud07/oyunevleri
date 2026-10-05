'use strict';

(() => {
  const root = document.querySelector('[data-address-map-root]');
  if (!root) return;

  const points = JSON.parse(root.dataset.points || '[]');
  const businesses = JSON.parse(root.dataset.businesses || '[]');
  const activeStudentsOnly = root.querySelector('[data-active-students-only]');
  const heatmapToggle = root.querySelector('[data-heatmap-toggle]');
  const visibleStudentCount = root.querySelector('[data-visible-student-count]');
  const isStudentVisible = (item) => !activeStudentsOnly?.checked || item.aktif_randevusu_var === true || Number(item.aktif_randevusu_var) === 1;
  const syncStudentFilter = () => {
    const visiblePoints = points.filter(isStudentVisible).length;
    if (visibleStudentCount) visibleStudentCount.textContent = `${visiblePoints} / ${points.length} öğrenci gösteriliyor`;
    let visibleRows = 0;
    document.querySelectorAll('[data-address-student-row]').forEach((row) => {
      const visible = !activeStudentsOnly?.checked || row.dataset.hasActiveAppointment === '1';
      row.hidden = !visible;
      if (visible) visibleRows++;
    });
    const emptyRow = document.querySelector('[data-address-filter-empty]');
    if (emptyRow) emptyRow.hidden = points.length === 0 || visibleRows > 0;
    const listCount = document.querySelector('[data-address-list-count]');
    if (listCount) listCount.textContent = `${visibleRows} adres`;
  };
  syncStudentFilter();
  activeStudentsOnly?.addEventListener('change', syncStudentFilter);

  const paintHeatmap = (canvas, width, height, projectedPoints, radius = 52) => {
    const pixelRatio = Math.max(1, window.devicePixelRatio || 1);
    canvas.width = Math.max(1, Math.round(width * pixelRatio));
    canvas.height = Math.max(1, Math.round(height * pixelRatio));
    canvas.style.width = `${width}px`;
    canvas.style.height = `${height}px`;
    const context = canvas.getContext('2d');
    context.setTransform(pixelRatio, 0, 0, pixelRatio, 0, 0);
    context.clearRect(0, 0, width, height);
    context.globalCompositeOperation = 'lighter';
    projectedPoints.forEach(({x, y}) => {
      if (x < -radius || y < -radius || x > width + radius || y > height + radius) return;
      const gradient = context.createRadialGradient(x, y, 0, x, y, radius);
      gradient.addColorStop(0, 'rgba(220, 38, 38, .72)');
      gradient.addColorStop(.22, 'rgba(249, 115, 22, .58)');
      gradient.addColorStop(.44, 'rgba(250, 204, 21, .43)');
      gradient.addColorStop(.66, 'rgba(34, 197, 94, .28)');
      gradient.addColorStop(.84, 'rgba(6, 182, 212, .18)');
      gradient.addColorStop(1, 'rgba(37, 99, 235, 0)');
      context.fillStyle = gradient;
      context.fillRect(x - radius, y - radius, radius * 2, radius * 2);
    });
    context.globalCompositeOperation = 'source-over';
  };
  const businessLabels = {okul: 'Okul', anaokulu: 'Anaokulu', kres: 'Kreş', oyun_evi: 'Oyun evi', cocuk_parki: 'Oyun alanı / çocuk parkı', diger: 'Diğer'};
  const businessColors = {okul: '#7c3aed', anaokulu: '#059669', kres: '#0891b2', oyun_evi: '#db2777', cocuk_parki: '#65a30d', diger: '#64748b'};
  const businessSourceLabel = (business) => business.kaynak === 'google_places' ? 'Google Places' : 'OpenStreetMap verisi';
  const activeBusinessCategories = () => new Set(Array.from(root.querySelectorAll('[data-business-filters] input:checked')).map((input) => input.value));
  const districtCenters = {
    'muratpaşa': [36.8888637, 30.7208911],
    'muratpasa': [36.8888637, 30.7208911],
    'kepez': [36.9176296, 30.7149914],
    'konyaaltı': [36.8726128, 30.6503649],
    'konyaalti': [36.8726128, 30.6503649],
    'döşemealtı': [37.0229560, 30.6013333],
    'dosemealti': [37.0229560, 30.6013333],
    'aksu': [36.9475051, 30.8477050]
  };
  const normalizeDistrict = (value) => String(value || '').trim().toLocaleLowerCase('tr-TR');
  const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[char]));
  const clusterColor = (count) => count >= 10 ? '#dc2626' : (count >= 5 ? '#ea580c' : '#2563eb');
  const googleClusterDistance = (zoom) => {
    if (zoom >= 16) return 0;
    return {15: .0007, 14: .0018, 13: .004, 12: .008, 11: .016}[zoom] || .032;
  };
  const geoClusters = (locations, zoom) => {
    const threshold = googleClusterDistance(zoom);
    if (threshold === 0) return locations.map((location) => ({lat: location.position.lat, lng: location.position.lng, members: [location]}));
    const clusters = [];
    locations.forEach((location) => {
      let cluster = clusters.find((candidate) => {
        const latDistance = candidate.lat - location.position.lat;
        const lngDistance = (candidate.lng - location.position.lng) * Math.cos(location.position.lat * Math.PI / 180);
        return Math.sqrt((latDistance ** 2) + (lngDistance ** 2)) <= threshold;
      });
      if (!cluster) {
        cluster = {lat: location.position.lat, lng: location.position.lng, members: []};
        clusters.push(cluster);
      }
      cluster.members.push(location);
      cluster.lat = cluster.members.reduce((sum, member) => sum + member.position.lat, 0) / cluster.members.length;
      cluster.lng = cluster.members.reduce((sum, member) => sum + member.position.lng, 0) / cluster.members.length;
    });
    return clusters;
  };

  if (root.dataset.googleEnabled === '1' && window.google?.maps) {
    const mapElement = root.querySelector('[data-address-map]');
    const googleMap = new google.maps.Map(mapElement, {
      center: {lat: 36.8888637, lng: 30.7208911},
      zoom: 11,
      mapTypeControl: false,
      streetViewControl: false,
      fullscreenControl: true
    });
    const geocoder = new google.maps.Geocoder();
    const infoWindow = new google.maps.InfoWindow();
    const googleBounds = new google.maps.LatLngBounds();
    const status = document.querySelector('[data-geocode-status]');
    const businessMarkers = businesses.map((business) => {
      const position = {lat: business.enlem, lng: business.boylam};
      const marker = new google.maps.Marker({
        map: googleMap,
        position,
        title: business.ad,
        icon: {
          path: google.maps.SymbolPath.CIRCLE,
          fillColor: businessColors[business.kategori] || businessColors.diger,
          fillOpacity: .95,
          strokeColor: '#ffffff',
          strokeWeight: 2,
          scale: 8
        },
        zIndex: 20
      });
      marker.addListener('click', () => {
        const address = [business.adres, business.ilce].filter(Boolean).map(escapeHtml).join(', ');
        infoWindow.setContent(`<strong>${escapeHtml(business.ad)}</strong><br>${escapeHtml(businessLabels[business.kategori] || 'Çocuk işletmesi')}${address ? `<br>${address}` : ''}<br><em>${escapeHtml(businessSourceLabel(business))}</em>`);
        infoWindow.open({map: googleMap, anchor: marker});
      });
      if (activeBusinessCategories().has(business.kategori)) googleBounds.extend(position);
      return {business, marker};
    });
    root.querySelector('[data-business-filters]')?.addEventListener('change', () => {
      const active = activeBusinessCategories();
      businessMarkers.forEach(({business, marker}) => marker.setMap(active.has(business.kategori) ? googleMap : null));
    });
    const initialBusinessCategories = activeBusinessCategories();
    businessMarkers.forEach(({business, marker}) => marker.setMap(initialBusinessCategories.has(business.kategori) ? googleMap : null));

    const geocode = (item) => new Promise((resolve) => {
      const address = [item.adres, item.ilce, item.il, 'Türkiye'].filter(Boolean).join(', ');
      if (!item.adres) {
        resolve(null);
        return;
      }
      geocoder.geocode({address, componentRestrictions: {country: 'TR'}, region: 'TR'}, (results, resultStatus) => {
        if (resultStatus !== 'OK' || !results?.[0]) {
          resolve(null);
          return;
        }
        const location = results[0].geometry.location;
        const lat = location.lat();
        const lng = location.lng();
        resolve(lat >= 35 && lat <= 43 && lng >= 25 && lng <= 45 ? {lat, lng} : null);
      });
    });

    const studentLocations = [];
    let studentMarkers = [];
    let googleHeatmap = null;
    const visibleGoogleLocations = () => studentLocations.filter(({item}) => isStudentVisible(item));
    const renderGoogleStudents = () => {
      studentMarkers.forEach((marker) => marker.setMap(null));
      studentMarkers = [];
      geoClusters(visibleGoogleLocations(), googleMap.getZoom() || 11).forEach((cluster) => {
        const count = cluster.members.length;
        const isCluster = count > 1;
        const onlyMember = cluster.members[0];
        const marker = new google.maps.Marker({
          map: googleMap,
          position: {lat: cluster.lat, lng: cluster.lng},
          title: isCluster ? `${count} kişi · yakınlaştırmak için tıklayın` : `${onlyMember.item.ogrenci} / ${onlyMember.item.veli}`,
          label: isCluster ? {text: String(count), color: '#ffffff', fontSize: '13px', fontWeight: '800'} : undefined,
          icon: {
            path: google.maps.SymbolPath.CIRCLE,
            fillColor: isCluster ? clusterColor(count) : (onlyMember.approximate ? '#f59e0b' : '#2563eb'),
            fillOpacity: .94,
            strokeColor: '#ffffff',
            strokeWeight: 3,
            scale: isCluster ? Math.min(28, 14 + Math.sqrt(count) * 3) : 9
          },
          zIndex: 100 + count
        });
        marker.addListener('click', () => {
          if (isCluster) {
            googleMap.setCenter({lat: cluster.lat, lng: cluster.lng});
            googleMap.setZoom(Math.min(18, (googleMap.getZoom() || 11) + 2));
            return;
          }
          const {item, approximate} = onlyMember;
          infoWindow.setContent(`<strong>${escapeHtml(item.veli)}</strong><br>${escapeHtml(item.ogrenci)}<br>${escapeHtml(item.adres)}<br><em>${approximate ? 'İlçe bazlı yaklaşık konum' : 'Doğrulanmış adres konumu'}</em>`);
          infoWindow.open({map: googleMap, anchor: marker});
        });
        studentMarkers.push(marker);
      });
    };
    class GoogleStudentHeatmap extends google.maps.OverlayView {
      constructor() {
        super();
        this.canvas = null;
      }
      onAdd() {
        this.canvas = document.createElement('canvas');
        this.canvas.className = 'student-heatmap-canvas';
        this.canvas.style.position = 'absolute';
        this.canvas.style.pointerEvents = 'none';
        this.getPanes().overlayLayer.appendChild(this.canvas);
      }
      draw() {
        if (!this.canvas || heatmapToggle?.checked === false) {
          if (this.canvas) this.canvas.style.display = 'none';
          return;
        }
        this.canvas.style.display = 'block';
        const bounds = googleMap.getBounds();
        const projection = this.getProjection();
        if (!bounds || !projection) return;
        const northEast = projection.fromLatLngToDivPixel(bounds.getNorthEast());
        const southWest = projection.fromLatLngToDivPixel(bounds.getSouthWest());
        const width = Math.max(1, northEast.x - southWest.x);
        const height = Math.max(1, southWest.y - northEast.y);
        this.canvas.style.left = `${southWest.x}px`;
        this.canvas.style.top = `${northEast.y}px`;
        const projected = visibleGoogleLocations().map(({position}) => {
          const point = projection.fromLatLngToDivPixel(new google.maps.LatLng(position));
          return {x: point.x - southWest.x, y: point.y - northEast.y};
        });
        paintHeatmap(this.canvas, width, height, projected, 58);
      }
      onRemove() {
        this.canvas?.remove();
        this.canvas = null;
      }
    }
    const renderGoogleHeatmap = () => {
      if (!googleHeatmap) {
        googleHeatmap = new GoogleStudentHeatmap();
        googleHeatmap.setMap(googleMap);
      }
      googleHeatmap.draw();
    };

    activeStudentsOnly?.addEventListener('change', () => {
      renderGoogleStudents();
      renderGoogleHeatmap();
    });
    heatmapToggle?.addEventListener('change', renderGoogleHeatmap);

    (async () => {
      let geocodedCount = 0;
      let processed = 0;
      const automaticCandidates = points.filter((item) => !item.dogrulandi && item.adres).slice(0, 25);
      if (status && automaticCandidates.length) status.textContent = `${automaticCandidates.length} adres sokak seviyesinde bulunuyor...`;

      for (const item of points) {
        let position = item.dogrulandi && Number.isFinite(item.enlem) && Number.isFinite(item.boylam)
          ? {lat: item.enlem, lng: item.boylam}
          : null;
        let approximate = false;
        if (!position && automaticCandidates.includes(item)) {
          position = await geocode(item);
          processed++;
          if (position) {
            item.enlem = position.lat;
            item.boylam = position.lng;
            item.dogrulandi = true;
            try {
              await talyaAjax('ogrenci_adres_konumu_guncelle', {ogrenci_id: item.ogrenci_id, adres_anahtari: item.adres_anahtari, enlem: position.lat, boylam: position.lng});
              geocodedCount++;
            } catch (error) {
              // Konum yine de bu oturumda gösterilir; kayıt hatası sonraki açılışta tekrar denenir.
            }
          }
          if (status) status.textContent = `${processed}/${automaticCandidates.length} adres işlendi`;
        }
        if (!position) {
          const center = districtCenters[normalizeDistrict(item.ilce)];
          if (center) {
            position = {lat: center[0], lng: center[1]};
            approximate = true;
          }
        }
        if (position) {
          studentLocations.push({item, position, approximate});
          googleBounds.extend(position);
        }
      }

      if (!googleBounds.isEmpty()) googleMap.fitBounds(googleBounds, 48);
      renderGoogleStudents();
      renderGoogleHeatmap();
      googleMap.addListener('zoom_changed', renderGoogleStudents);
      const visibleCount = visibleGoogleLocations().length;
      if (status) status.textContent = geocodedCount ? `${geocodedCount} adres bulundu · haritada ${visibleCount} kişi` : `Haritada ${visibleCount} kişi gösteriliyor`;
    })();
    return;
  }

  if (typeof window.L === 'undefined') return;

  const mapped = points.map((item) => {
    const exact = item.dogrulandi && Number.isFinite(item.enlem) && Number.isFinite(item.boylam);
    const center = districtCenters[normalizeDistrict(item.ilce)];
    if (exact) return {...item, mapLat: item.enlem, mapLng: item.boylam, approximate: false};
    if (center) return {...item, mapLat: center[0], mapLng: center[1], approximate: true};
    return null;
  }).filter(Boolean);
  const tileUrl = root.dataset.tileUrl || 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
  const tileOptions = {maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'};

  const map = L.map(root.querySelector('[data-address-map]')).setView([36.8841, 30.7056], 11);
  L.tileLayer(tileUrl, tileOptions).addTo(map);
  const bounds = mapped.map((item) => [item.mapLat, item.mapLng]);
  const studentLayer = L.layerGroup().addTo(map);
  map.createPane('studentHeatPane');
  map.getPane('studentHeatPane').style.zIndex = '350';
  map.getPane('studentHeatPane').style.pointerEvents = 'none';
  const leafletHeatmap = document.createElement('canvas');
  leafletHeatmap.className = 'student-heatmap-canvas leaflet-zoom-hide';
  leafletHeatmap.style.position = 'absolute';
  leafletHeatmap.style.pointerEvents = 'none';
  map.getPane('studentHeatPane').appendChild(leafletHeatmap);
  const visibleLeafletStudents = () => mapped.filter(isStudentVisible);
  const leafletClusters = () => {
    const zoom = map.getZoom();
    const visible = visibleLeafletStudents();
    if (zoom >= 16) return visible.map((item) => ({x: 0, y: 0, members: [item]}));
    const clusters = [];
    visible.forEach((item) => {
      const pixel = map.project([item.mapLat, item.mapLng], zoom);
      let cluster = clusters.find((candidate) => Math.hypot(candidate.x - pixel.x, candidate.y - pixel.y) <= 72);
      if (!cluster) {
        cluster = {x: pixel.x, y: pixel.y, members: []};
        clusters.push(cluster);
      }
      cluster.members.push(item);
      cluster.x = cluster.members.reduce((sum, member) => sum + map.project([member.mapLat, member.mapLng], zoom).x, 0) / cluster.members.length;
      cluster.y = cluster.members.reduce((sum, member) => sum + map.project([member.mapLat, member.mapLng], zoom).y, 0) / cluster.members.length;
    });
    return clusters;
  };
  const renderLeafletStudents = () => {
    studentLayer.clearLayers();
    leafletClusters().forEach((cluster) => {
      const count = cluster.members.length;
      if (count > 1) {
        const center = cluster.members.reduce((position, item) => [position[0] + item.mapLat / count, position[1] + item.mapLng / count], [0, 0]);
        const tier = count >= 10 ? 'high' : (count >= 5 ? 'medium' : 'low');
        const marker = L.marker(center, {
          title: `${count} kişi · yakınlaştırmak için tıklayın`,
          icon: L.divIcon({className: 'student-map-cluster', html: `<span class="is-${tier}">${count}</span>`, iconSize: [44, 44], iconAnchor: [22, 22]})
        }).addTo(studentLayer);
        marker.on('click', () => map.setView(center, Math.min(18, map.getZoom() + 2)));
        return;
      }
      const item = cluster.members[0];
      const marker = L.circleMarker([item.mapLat, item.mapLng], {
        radius: 9,
        color: '#ffffff',
        fillColor: item.approximate ? '#f59e0b' : '#2563eb',
        fillOpacity: .95,
        weight: 3
      }).addTo(studentLayer);
      const locationNote = item.approximate ? '<br><em>İlçe bazlı yaklaşık konum</em>' : '<br><em>Doğrulanmış adres konumu</em>';
      marker.bindPopup(`<strong>${escapeHtml(item.veli)}</strong><br>${escapeHtml(item.ogrenci)}<br>${escapeHtml(item.ilce)} / ${escapeHtml(item.il)}${locationNote}`);
    });
  };
  const renderLeafletHeatmap = () => {
    leafletHeatmap.style.display = heatmapToggle?.checked === false ? 'none' : 'block';
    if (heatmapToggle?.checked === false) return;
    const size = map.getSize();
    const topLeft = map.containerPointToLayerPoint([0, 0]);
    L.DomUtil.setPosition(leafletHeatmap, topLeft);
    const projected = visibleLeafletStudents().map((item) => {
      const point = map.latLngToContainerPoint([item.mapLat, item.mapLng]);
      return {x: point.x, y: point.y};
    });
    paintHeatmap(leafletHeatmap, size.x, size.y, projected, 54);
  };
  map.on('zoomend', () => {
    renderLeafletStudents();
    renderLeafletHeatmap();
  });
  map.on('moveend resize', renderLeafletHeatmap);
  renderLeafletStudents();
  renderLeafletHeatmap();
  activeStudentsOnly?.addEventListener('change', () => {
    renderLeafletStudents();
    renderLeafletHeatmap();
  });
  heatmapToggle?.addEventListener('change', renderLeafletHeatmap);
  const businessLayers = businesses.map((business) => {
    const marker = L.circleMarker([business.enlem, business.boylam], {
      radius: 8,
      color: '#ffffff',
      weight: 2,
      fillColor: businessColors[business.kategori] || businessColors.diger,
      fillOpacity: .95
    });
    const address = [business.adres, business.ilce].filter(Boolean).map(escapeHtml).join(', ');
    marker.bindPopup(`<strong>${escapeHtml(business.ad)}</strong><br>${escapeHtml(businessLabels[business.kategori] || 'Çocuk işletmesi')}${address ? `<br>${address}` : ''}<br><em>${escapeHtml(businessSourceLabel(business))}</em>`);
    if (activeBusinessCategories().has(business.kategori)) {
      marker.addTo(map);
      bounds.push([business.enlem, business.boylam]);
    }
    return {business, marker};
  });
  root.querySelector('[data-business-filters]')?.addEventListener('change', () => {
    const active = activeBusinessCategories();
    businessLayers.forEach(({business, marker}) => {
      if (active.has(business.kategori) && !map.hasLayer(marker)) marker.addTo(map);
      if (!active.has(business.kategori) && map.hasLayer(marker)) map.removeLayer(marker);
    });
  });
  if (bounds.length) {
    const hasExactLocation = mapped.some((item) => !item.approximate);
    map.fitBounds(bounds, {padding: [28, 28], maxZoom: hasExactLocation ? 14 : 11});
  }

  const dialog = document.querySelector('[data-location-dialog]');
  if (!dialog) return;
  const form = dialog.querySelector('[data-location-form]');
  let pickerMap;
  let pickerMarker;

  const setLocation = (latlng) => {
    form.elements.enlem.value = latlng.lat.toFixed(7);
    form.elements.boylam.value = latlng.lng.toFixed(7);
    if (!pickerMarker) {
      pickerMarker = L.marker(latlng, {draggable: true}).addTo(pickerMap);
      pickerMarker.on('dragend', () => setLocation(pickerMarker.getLatLng()));
    } else {
      pickerMarker.setLatLng(latlng);
    }
  };

  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-location-edit]');
    if (!trigger) return;
    const item = points.find((point) => String(point.ogrenci_id) === trigger.dataset.locationEdit);
    if (!item) return;
    form.reset();
    form.elements.ogrenci_id.value = item.ogrenci_id;
    dialog.querySelector('[data-location-student]').textContent = `${item.ogrenci} / ${item.veli}`;
    dialog.querySelector('[data-location-address]').textContent = [item.adres, item.ilce, item.il].filter(Boolean).join(', ');
    dialog.querySelector('[data-location-message]').textContent = '';
    dialog.showModal();
    setTimeout(() => {
      const districtCenter = districtCenters[normalizeDistrict(item.ilce)] || [36.8888637, 30.7208911];
      const initial = item.dogrulandi ? [item.enlem, item.boylam] : districtCenter;
      if (!pickerMap) {
        pickerMap = L.map(dialog.querySelector('[data-location-picker-map]')).setView(initial, item.dogrulandi ? 16 : 13);
        L.tileLayer(tileUrl, tileOptions).addTo(pickerMap);
        pickerMap.on('click', (mapEvent) => setLocation(mapEvent.latlng));
      } else {
        pickerMap.setView(initial, item.dogrulandi ? 16 : 13);
        pickerMap.invalidateSize();
      }
      if (pickerMarker) { pickerMap.removeLayer(pickerMarker); pickerMarker = null; }
      if (item.dogrulandi) setLocation({lat: item.enlem, lng: item.boylam});
    }, 80);
  });

  document.addEventListener('click', (event) => {
    if (event.target.closest('[data-location-close]')) dialog.close();
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const message = dialog.querySelector('[data-location-message]');
    if (!form.elements.enlem.value || !form.elements.boylam.value) {
      message.textContent = 'Önce haritada bir konum seçin.';
      return;
    }
    const button = form.querySelector('button[type="submit"]');
    button.disabled = true;
    try {
      const result = await talyaAjax('ogrenci_adres_konumu_guncelle', Object.fromEntries(new FormData(form).entries()));
      message.textContent = result.mesaj;
      window.location.reload();
    } catch (error) {
      message.textContent = error.message;
    } finally {
      button.disabled = false;
    }
  });
})();
