<script>
	$(document).ready(function () {

		<?php
			// işlem başarılı
			if( isset($_SESSION['process_result_success']) ) { 
				unset($_SESSION['process_result_success']);
				echo 'islem_basarili_snackbar();';
			} else if( isset($_SESSION['process_result_error']) ) { 
				unset($_SESSION['process_result_error']);
				echo 'islem_hatali_snackbar();';
			}
		?>

		// ###### SELECT ACTIVE MENU BY URL ###### //
		/*
		const queryString = window.location.search;
		const urlParams = new URLSearchParams(queryString);
		let module = urlParams.get('module')

		if (module) {

			if (module == 'grup') {
				let tipi = urlParams.get('tipi');
				module = tipi;
			}

		$('#vuiinMenu li.menu').each(function () {
				var $this = $(this);
				$this.removeClass('active');
			});

		$('#vuiinMenu li').each(function () {
				var $this = $(this);
				// if the current path is like this link, make it active
				if ($this.data('module') == module) {
					$this.addClass('active');
					$this.children('a').attr("aria-expanded", "true");
					$this.children('ul').addClass('show');
				}
			});

		}
		*/

		// Up - Down
		var yukseklik = $(document).height();
		if (yukseklik < 1000) {$("#up_down").hide(); }

		/*
			dt = $('.datatable-init-export').DataTable({
			responsive: {
			details: true
				},
		paging: false,
		// ordering: false,
		// dom: 'lfi',
		buttons: ['copy', 'excel', 'csv', 'pdf']
			});
		*/

		loadOdemeSekli();

		let cari_id = document.getElementById("cari_id");

		if(cari_id) {

			cari_select2 = new TomSelect('#cari_id', {
				valueField: 'cari_id',
				labelField: 'cari_unvani',
				searchField: 'cari_unvani',
				// fetch remote data
				load: function (query, callback) {
					var url = 'ajax.php?action=cari_ara&ara=' + encodeURIComponent(query);
					fetch(url)
							.then(response => response.json())
							.then(json => {
								callback(json.items);
							}).catch(() => {
								callback();
							});

				},
				// custom rendering functions for options and items
				render: {
					option: function (item, escape) {
							return `<div class="py-2 d-flex">${item.cari_unvani}</div>`;
						},
				item: function (item, escape) {
							return `<div class="py-2 d-flex">${item.cari_unvani}</div>`;
						}
					},
				});
		}
		

	});


	// ####################### START ÖDEME ŞEKLİ SELECT #######################
	/*$(document).on('change', '#kasa_id', function () {
		loadCurrencyDetails();
	});*/
	
	$(document).on('change', '.pb_id', function () {
		loadCurrencyDetails();
	});

	function loadCurrencyDetails() {

		<?php if ( !empty($row['ch_id']) ) { ?>
			console.log('kasa_id:'+<?=$row['kasa_id']?>);
			$('#kasa_id').val(<?=$row['kasa_id']?>);
		<?php } ?>

		/*
		if (document.getElementById('kasa_id')) {
			// get currency and add to label
			console.log('burda1');
			let pb_id = $('#kasa_id').find(':selected').data('pb_id');
			let pb_text = $('#kasa_id').find(':selected').data('pb_text');
			if (pb_id > 0) {
				// console.log('pb_id:'+pb_id+' pb_text:'+pb_text)
				$('#pb_id').val(pb_id);
				$('#pb_text').val(pb_text);
				$('.currency_label').html(pb_text);
			} 
		} else {
			let pb_id = $('.pb_id').val();
			let pb_text = $('.pb_id').find(':selected').data('pb_text');
			let pb_kodu = $('.pb_id').find(':selected').data('pb_kodu');
			if (pb_id > 0) {
				// console.log('pb_id:'+pb_id+' pb_text:'+pb_text)
				$('#pb_id').val(pb_id);
				$('#pb_text').val(pb_text);
				$('#pb_kodu').val(pb_kodu);
				$('.currency_label').html(pb_text);
			} 
		}
		*/

		let pb_id = $('.pb_id').val();
		let pb_text = $('.pb_id').find(':selected').data('pb_text');
		let pb_kodu = $('.pb_id').find(':selected').data('pb_kodu');
		if (pb_id > 0) {
			// console.log('pb_id:'+pb_id+' pb_text:'+pb_text)
			$('#pb_id').val(pb_id);
			$('#pb_text').val(pb_text);
			$('#pb_kodu').val(pb_kodu);
			$('.currency_label').html(pb_text);
		} 
		
	}

	$(document).on('change', '#os_id', function () {
		loadOdemeSekli();
	});

	function loadOdemeSekli() {

		let os_id = $('#os_id').val();

		const queryString = window.location.search;
		const urlParams = new URLSearchParams(queryString);
		let page = urlParams.get('page')

		$.ajax({
			type: 'POST',
			url: 'ajax',
			data: {action: 'odeme_sekli_bul', os_id: os_id, kasa_id: <?php echo $selected_kasa_id ?? 0; ?>, kasa_goster: 1, page: page },
			success: function (ajaxCevap) {
				$('#odeme_sekli').html(ajaxCevap);
				loadCurrencyDetails();
			}
		});

	}

	// ####################### END ÖDEME ŞEKLİ SELECT #######################


	// ####################### START KASA SELECT #######################
	$(document).on('change', '.kasa', function () {

		let pb_id = $(this).find(':selected').attr('data-pb_id');
		let pb_text = $(this).find(':selected').attr('data-pb_text');

		$('#odeme_pb_id').val(pb_id);
		$('#pb_value').val(pb_id);
		$('#pb_value').text(pb_text);
		$('.para_birimi').html(pb_text);

	});
	// ####################### END KASA SELECT #######################


	$(document).on('focus', ".timepicker", function () {
		$(this).mask("99:99");
});

	$(document).on('focus', ".numerik", function () {
		$(this).virgul2nokta();
});

	$(document).on("change", ".changeStatus", function () {

		let table = $(this).data('table');
	let id_name = $(this).attr('name');
	let id_val = $(this).val();

	if ($(this).is(':checked')) {
		durum = 1;
	} else {
		durum = 0;
	}

	$.ajax({
		type: "POST",
		url: 'ajax',
		data: {action: 'change_status', durum: durum, table: table, id_name: id_name, id_val: id_val },
		success: function (data) {
			if (data == 1) {
				Snackbar.show({
					text: 'Durum başarıyla değiştirildi',
					actionText: 'X',
					actionTextColor: '#fff',
					backgroundColor: '#00ab55'
				});
			}
		}
	});

});

	function scrollToBottom() {
		$('html, body').animate({ scrollTop: $(document).height() }, 'slow');
}

	function scrollToTop() {
		$('html, body').animate({ scrollTop: 0 }, 'slow');
}

	function islem_basarili_alert() {
		Swal.fire({
			// position: 'top-end',
			icon: 'success',
			title: 'İşlem Başarılı',
			showConfirmButton: false,
			timer: 1500
		});
}

	function islem_basarili_snackbar() {
		Snackbar.show({
			text: 'İşlem Başarılı',
			actionText: 'X',
			actionTextColor: '#fff',
			backgroundColor: '#00ab55'
		});
	}

	function islem_hatali_snackbar() {
		Snackbar.show({
			text: 'Hata Oluştu',
			actionText: 'X',
			actionTextColor: '#fff',
			backgroundColor: '#e7515a'
		});
	}

	// const result = countString(string, letterToCheck);
	function countString(str, letter) {
		let count = 0;

	// looping through the items
	for (let i = 0; i < str.length; i++) {

		// check if the character is at that position
		if (str.charAt(i) == letter) {
		count += 1;
		}
	}
	return count;
}

	// ################################ VIIRGUL 2 NOKTA ################################ //
	// INPUT alanina virgul girildiginde noktaya cevirir. Sadece RAKAM girisini saglar.....
	// 2013-02-21 (YK)
	(function ($) {
		$.fn.virgul2nokta = function () {
			this.each(function () {

				$(this).keyup(function () {
					this.value = this.value.replace(/,/g, '.');
					this.value = this.value.replace(/#/g, '.');
					if (this.value.match(/[^0-9\.]/)) {
						this.value = this.value.replace(/[^0-9\.]/g, '');
					}

					let sayi = countString(this.value, '.')

					if (sayi > 1) {
						// console.log(this.value);
						this.value = this.value.replace(/.$/, '');
					}

				});

			});
			return this;
		};
})(jQuery);
	// ################################ VIIRGUL 2 NOKTA END ################################ //

	// ################################ MASK ################################ //
	// INPUT alanina virgul girildiginde noktaya cevirir. Sadece RAKAM girisini saglar.....
	// 2013-02-21 (YK)
	(function ($) {
		$.fn.virgul2nokta = function () {
			this.each(function () {

				$(this).keyup(function () {
					this.value = this.value.replace(/,/g, '.');
					this.value = this.value.replace(/#/g, '.');
					if (this.value.match(/[^0-9\.]/)) {
						this.value = this.value.replace(/[^0-9\.]/g, '');
					}

					let sayi = countString(this.value, '.')

					if (sayi > 1) {
						// console.log(this.value);
						this.value = this.value.replace(/.$/, '');
					}

				});

			});
			return this;
		};
})(jQuery);
	// ################################ VIIRGUL 2 NOKTA END ################################ //

	function db2trDate(tarih) {
		dates = tarih.split("-");
	let new_date = dates[2] + '.' + dates[1] + '.' + dates[0];
	return new_date;
}

	function tr2dbDate(tarih) {
		dates = tarih.split(".");
	let new_date = dates[2] + '-' + dates[1] + '-' + dates[0];
	return new_date;
}
</script>