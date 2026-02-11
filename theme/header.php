<?php
$aktif_donem_adi = '';
$aktif_kampus_adi = $_SESSION['kampus_adi'] ?? '';
$kullanici_kampusler = [];
$kullanici_id = $_SESSION['kullanici']['id'] ?? null;
$kampus_filtre_all = !empty($_SESSION['kampus_filtre_all']);
if (!empty($db) && !empty($kullanici_id)) {
    try {
        $sql = "SELECT kk.kampus_id, k.kampus_adi FROM kullanici_kampus_yetkileri kk INNER JOIN kampusler k ON k.id = kk.kampus_id WHERE kk.kullanici_id = :kullanici_id ORDER BY k.kampus_adi";
        $stmt = $db->prepare($sql);
        $stmt->execute(['kullanici_id' => $kullanici_id]);
        $kullanici_kampusler = $stmt->fetchAll();
    } catch (PDOException $e) {
        $kullanici_kampusler = [];
        error_log('Kampus yetki liste hata: ' . $e->getMessage());
    }
}

if (!empty($db) && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_kampus') {
    $kampus_id = (int) ($_POST['kampus_id'] ?? 0);
    $kullanici_id = $_SESSION['kullanici']['id'] ?? null;
    if (!empty($kullanici_id) && $kampus_id > 0) {
        try {
            $sql = "SELECT k.id, k.kampus_adi FROM kullanici_kampus_yetkileri kk INNER JOIN kampusler k ON k.id = kk.kampus_id WHERE kk.kullanici_id = :kullanici_id AND kk.kampus_id = :kampus_id";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'kullanici_id' => $kullanici_id,
                'kampus_id' => $kampus_id,
            ]);
            $kampus = $stmt->fetch();
            if ($kampus) {
                $_SESSION['kampus_id'] = $kampus['id'];
                $_SESSION['kampus_adi'] = $kampus['kampus_adi'];
            }
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        } catch (PDOException $e) {
            error_log('Kampus degistir hata: ' . $e->getMessage());
        }
    }
}
if (!empty($db) && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_kampus_filter') {
    if (!empty($kullanici_kampusler) && count($kullanici_kampusler) > 1) {
        $_SESSION['kampus_filtre_all'] = !empty($_POST['kampus_filter_all']) ? 1 : 0;
        $kampus_filtre_all = !empty($_SESSION['kampus_filtre_all']);
    }
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}
if (!empty($db)) {
    try {
        $sql = "SELECT donem_adi FROM donemler WHERE aktif = 1 LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $aktif_donem_adi = (string) $stmt->fetchColumn();
    } catch (PDOException $e) {
        $aktif_donem_adi = '';
        error_log('Aktif donem hata: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <?php
        $script_name = $_SERVER['SCRIPT_NAME'] ?? '/';
        $base_href = preg_replace('#/modules/.*$#', '/', $script_name);
        if ($base_href === '') {
            $base_href = '/';
        }
        $base_href = rtrim($base_href, '/') . '/';
    ?>
    <base href="<?php echo $base_href; ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <title>Envar</title>
    <link rel="shortcut icon" href="theme/custom/favicon.svg">
    <link href="theme/layouts/horizontal-light-menu/css/light/loader.css" rel="stylesheet" type="text/css" />
    <link href="theme/layouts/horizontal-light-menu/css/dark/loader.css" rel="stylesheet" type="text/css" />
    <script>firma_logo = '/assets/logo.png'</script>
    <script src="theme/layouts/horizontal-light-menu/loader.js"></script>

    <!-- BEGIN GLOBAL MANDATORY STYLES -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Material+Icons|Material+Icons+Outlined|Material+Icons+Two+Tone|Material+Icons+Round|Material+Icons+Sharp" rel="stylesheet">
    <link href="theme/src/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="theme/layouts/horizontal-light-menu/css/light/plugins.css" rel="stylesheet" type="text/css" />
    <link href="theme/layouts/horizontal-light-menu/css/dark/plugins.css" rel="stylesheet" type="text/css" />
    <!-- END GLOBAL MANDATORY STYLES -->

    <link href="theme/src/plugins/src/font-icons/fontawesome/css/regular.css" rel="stylesheet">
    <link href="theme/src/plugins/src/font-icons/fontawesome/css/fontawesome.css" rel="stylesheet">

    <link type="text/css" href="theme/src/plugins/src/tomSelect/tom-select.default.min.css" rel="stylesheet">
    <link type="text/css" href="theme/src/plugins/css/light/tomSelect/custom-tomSelect.css" rel="stylesheet">
    <link type="text/css" href="theme/src/plugins/css/dark/tomSelect/custom-tomSelect.css" rel="stylesheet">

    <link href="theme/src/plugins/src/flatpickr/flatpickr.css" rel="stylesheet" type="text/css">
    <link href="theme/src/plugins/css/dark/flatpickr/custom-flatpickr.css" rel="stylesheet" type="text/css">

    <!-- BEGIN PAGE LEVEL PLUGINS/CUSTOM STYLES -->
    <link href="theme/src/plugins/src/apex/apexcharts.css" rel="stylesheet" type="text/css">
    <link href="theme/src/assets/css/light/dashboard/dash_1.css" rel="stylesheet" type="text/css" />
    <link href="theme/src/assets/css/dark/dashboard/dash_1.css" rel="stylesheet" type="text/css" />
    <!-- END PAGE LEVEL PLUGINS/CUSTOM STYLES -->

    <!--  datatables --->
    <link rel="stylesheet" type="text/css" href="theme/src/plugins/src/table/datatable/datatables.css">
    <link rel="stylesheet" type="text/css" href="theme/src/plugins/css/light/table/datatable/dt-global_style.css">
    <link rel="stylesheet" type="text/css" href="theme/src/plugins/css/dark/table/datatable/dt-global_style.css">

    <!--  switches --->
    <link rel="stylesheet" type="text/css" href="theme/src/assets/css/dark/forms/switches.css">
    <link rel="stylesheet" type="text/css" href="theme/src/assets/css/light/forms/switches.css">

    <!--  modal --->
    <link href="theme/src/assets/css/light/components/modal.css" rel="stylesheet" type="text/css" />
    <link href="theme/src/assets/css/dark/components/modal.css" rel="stylesheet" type="text/css" />

    <!--  sweetalert2 --->
    <link href="theme/src/plugins/src/sweetalerts2/sweetalerts2.css" rel="stylesheet">
    <link href="theme/src/assets/css/light/scrollspyNav.css" rel="stylesheet" type="text/css" />
    <link href="theme/src/plugins/css/light/sweetalerts2/custom-sweetalert.css" rel="stylesheet" type="text/css" />
    <link href="theme/src/assets/css/dark/scrollspyNav.css" rel="stylesheet" type="text/css" />
    <link href="theme/src/plugins/css/dark/sweetalerts2/custom-sweetalert.css" rel="stylesheet" type="text/css" />

    <!-- toastr -->
    <link href="theme/src/plugins/src/notification/snackbar/snackbar.min.css" rel="stylesheet" type="text/css" />

    <link href="theme/src/assets/css/light/components/timeline.css" rel="stylesheet" type="text/css" />
    <link href="theme/src/assets/css/dark/components/timeline.css" rel="stylesheet" type="text/css" />

    <!--  tabs  -->
    <link href="theme/src/assets/css/light/components/tabs.css" rel="stylesheet" type="text/css" />
    <link href="theme/src/assets/css/dark/components/tabs.css" rel="stylesheet" type="text/css" />

    <!--  alerts  -->
    <link rel="stylesheet" type="text/css" href="theme/src/assets/css/light/elements/alert.css">
    <link rel="stylesheet" type="text/css" href="theme/src/assets/css/dark/elements/alert.css">

    <!--  tooltip  -->
    <link href="theme/src/assets/css/light/elements/tooltip.css" rel="stylesheet" type="text/css" />
    <link href="theme/src/assets/css/dark/elements/tooltip.css" rel="stylesheet" type="text/css" />
    
    <!-- autoComplete -->
    <link href="theme/src/plugins/src/autocomplete/css/autoComplete.02.css" rel="stylesheet" type="text/css" />
    <link href="theme/src/plugins/css/light/autocomplete/css/custom-autoComplete.css" rel="stylesheet" type="text/css" />
    <link href="theme/src/plugins/css/dark/autocomplete/css/custom-autoComplete.css" rel="stylesheet" type="text/css" />
    
    <!--  END CUSTOM STYLE FILE  -->

    <!--  custom --->
    <link rel="stylesheet" href="theme/custom/css/custom.css">

    <style>
        body.dark .layout-px-spacing, .layout-px-spacing {
            min-height: calc(100vh - 155px) !important;
        }
        
        .md-18 {
            font-size: 18px;
        }

        .md-24 {
            font-size: 24px;
        }

        .md-36 {
            font-size: 36px;
        }

        .md-48 {
            font-size: 48px;
        }
    </style>
</head>

<body class="layout-boxed" layout="full-width">

    <!-- BEGIN LOADER -->
    <div id="load_screen"> <div class="loader"> <div class="loader-content">
        <div class="spinner-grow align-self-center"></div>
    </div></div></div>
    <!--  END LOADER -->

    <!--  BEGIN NAVBAR  -->
    <div class="header-container d-print-none">
        <header class="header navbar navbar-expand-sm expand-header">
            <a href="javascript:void(0);" class="sidebarCollapse" data-placement="bottom"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-menu"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg></a>
            <ul class="navbar-item theme-brand flex-row  text-center">
                <li class="nav-item theme-logo">
                    <a href="./">
                        <img src="/assets/logo.png" class="navbar-logo" alt="logo" style="height: 44px !important;width: auto;">
                    </a>
                </li>
                <li> &nbsp; &nbsp; &nbsp; </li>
            </ul>

            <div class="search-animated toggle-search">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-search">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                
            </div>

            <ul class="navbar-item flex-row ms-lg-auto ms-0 action-area">
                <?php if (!empty($aktif_donem_adi)) { ?>
                    <li class="nav-item me-2">
                        <span class="badge badge-light-primary">Aktif Dönem: <?php echo htmlspecialchars($aktif_donem_adi, ENT_QUOTES, 'UTF-8'); ?></span>
                    </li>
                <?php } ?>
                <?php if (!empty($kullanici_kampusler)) { ?>
                    <li class="nav-item me-2">
                        <form method="post" action="">
                            <input type="hidden" name="action" value="set_kampus">
                            <select class="form-select form-select-sm" name="kampus_id" onchange="this.form.submit()">
                                <?php foreach ($kullanici_kampusler as $kampus) { ?>
                                    <option value="<?php echo (int) $kampus['kampus_id']; ?>" <?php echo ((int) ($_SESSION['kampus_id'] ?? 0) === (int) $kampus['kampus_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($kampus['kampus_adi'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </form>
                    </li>
                    <?php if (count($kullanici_kampusler) > 1) { ?>
                        <li class="nav-item me-2">
                            <form method="post" action="" class="d-flex align-items-center gap-2">
                                <input type="hidden" name="action" value="set_kampus_filter">
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" id="kampusFilterAll" name="kampus_filter_all" value="1" <?php echo $kampus_filtre_all ? 'checked' : ''; ?> onchange="this.form.submit()">
                                    <label class="form-check-label small" for="kampusFilterAll">Tüm Kampüsler</label>
                                </div>
                            </form>
                        </li>
                    <?php } ?>
                <?php } elseif (!empty($aktif_kampus_adi)) { ?>
                    <li class="nav-item me-2">
                        <span class="badge badge-light-secondary">Kampüs: <?php echo htmlspecialchars($aktif_kampus_adi, ENT_QUOTES, 'UTF-8'); ?></span>
                    </li>
                <?php } ?>
                <li class="nav-item theme-toggle-item">
                    <a href="javascript:void(0);" class="nav-link theme-toggle">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-moon dark-mode">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                        </svg>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-sun light-mode">
                            <circle cx="12" cy="12" r="5"></circle>
                            <line x1="12" y1="1" x2="12" y2="3"></line>
                            <line x1="12" y1="21" x2="12" y2="23"></line>
                            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                            <line x1="1" y1="12" x2="3" y2="12"></line>
                            <line x1="21" y1="12" x2="23" y2="12"></line>
                            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                        </svg>
                    </a>
                </li>
                <li class="nav-item ms-3 me-1">
                    <a href="javascript:void(0);" title="Geri Bildirim Gönder" class="nav-link showfeedbackFormModal">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-inbox">
                            <polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline>
                            <path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z">
                            </path>
                        </svg>
                    </a>
                </li>
                <li class="nav-item ms-3 me-1">
                    <a href="index.php?module=task_manager&page=takvim" title="Görev Takvimi" class="nav-link">
                        <i class="far fa-calendar-alt" style="font-size: 1.6em"></i>
                    </a>
                </li>
                <li class="nav-item dropdown user-profile-dropdown  order-lg-0 order-1">
                    <a href="javascript:void(0);" class="nav-link dropdown-toggle user" id="userProfileDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <div class="avatar-container">
                            <div class="avatar avatar-sm avatar-indicators avatar-online">
                                <img alt="avatar" src="theme/src/assets/img/profile-30.png" class="rounded-circle">
                            </div>
                        </div>
                    </a>
                    <div class="dropdown-menu position-absolute" aria-labelledby="userProfileDropdown">
                        <div class="user-profile-section">
                            <div class="media mx-auto">
                                <div class="emoji me-2">
                                    &#x1F44B;
                                </div>
                                <div class="media-body">
                                    <h5><?php echo $_SESSION['user']['personel_adsoyad']; ?></h5>
                                    <p><?php echo $_SESSION['user']['personel_kodu']; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="dropdown-item">
                            <a href="user-profile.html">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-user">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg> <span>Profile</span>
                            </a>
                        </div>
                        <div class="dropdown-item">
                            <a href="#" class="showfeedbackFormModal">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-inbox">
                                    <polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline>
                                    <path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z">
                                    </path>
                                </svg> <span>Geri Bildirim Gönder</span>
                            </a>
                        </div>
                        <div class="dropdown-item">
                            <a href="index.php?module=rapor&page=geri_bildirimler">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-inbox">
                                    <polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline>
                                    <path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z">
                                    </path>
                                </svg> <span>Geri Bildirimlerim</span>
                            </a>
                        </div>
                        <div class="dropdown-item">
                            <a href="index.php?module=personel&page=personel_yetkilerim">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-lock">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg> <span>Yetkilerim</span>
                            </a>
                        </div>
                        <div class="dropdown-item">
                            <a href="logout">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-log-out">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                    <polyline points="16 17 21 12 16 7"></polyline>
                                    <line x1="21" y1="12" x2="9" y2="12"></line>
                                </svg> <span>Log Out</span>
                            </a>
                        </div>
                    </div>
                </li>
            </ul>
        </header>
    </div>
    <!--  END NAVBAR  -->

    <!--  BEGIN MAIN CONTAINER  -->
    <div class="main-container" id="container">

        <div class="overlay"></div>
        <div class="search-overlay"></div>

        <!--  BEGIN SIDEBAR  -->
        <div class="sidebar-wrapper sidebar-theme">

            <nav id="sidebar">

                <div class="navbar-nav theme-brand flex-row  text-center">
                    <div class="nav-logo">
                        <div class="nav-item theme-logo"><a href="./"><img src="/logo.png" class="navbar-logo" alt="logo"></a></div>
                        <div class="nav-item theme-text"><a href="./" class="nav-link"> Muhasis </a></div>
                    </div>
                    <div class="nav-item sidebar-toggle">
                        <div class="btn-toggle sidebarCollapse">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevrons-left">
                                <polyline points="11 17 6 12 11 7"></polyline>
                                <polyline points="18 17 13 12 18 7"></polyline>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="shadow-bottom"></div>
                <ul class="list-unstyled menu-categories" id="vuiinMenu">
                    <li class="menu" data-module="crm">
                        <a href="modules/crm/aday_ogrenci.php" aria-expanded="false" class="dropdown-toggle">
                            <div class="">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-users">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                                <span>CRM / Ön Kayıt</span>
                            </div>
                        </a>
                    </li>

                    <li class="menu active" data-module="dashboard">
                        <a href="modules/dashboard.php" id="header_dashboard" aria-expanded="false" class="dropdown-toggle">
                            <div class="">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-home">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                                </svg>
                                <span>Dashboard</span>
                            </div>
                        </a>
                    </li>

                    <li class="menu" data-module="ogrenci_islemleri">
                        <a href="#ogrenci_islemleri_menu" data-bs-toggle="dropdown" aria-expanded="false" class="dropdown-toggle">
                            <div class="">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-book-open">
                                    <path d="M2 4h6a4 4 0 0 1 4 4v12a4 4 0 0 0-4-4H2z"></path>
                                    <path d="M22 4h-6a4 4 0 0 0-4 4v12a4 4 0 0 1 4-4h6z"></path>
                                </svg>
                                <span>Öğrenci İşlemleri</span>
                            </div>
                            <div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </div>
                        </a>
                        <ul class="dropdown-menu submenu list-unstyled" id="ogrenci_islemleri_menu" data-bs-parent="#vuiinMenu">
                            <li>
                                <a href="modules/ogrenci/ogrenci_listesi.php">Öğrenci Listesi</a>
                            </li>
                            <li>
                                <a href="modules/hak/hak_yonetimi.php">Hak Yönetimi</a>
                            </li>
                            <li>
                                <a href="modules/rezervasyon/rezervasyon_listesi.php">Rezervasyonlar</a>
                            </li>
                        </ul>
                    </li>

                    <li class="menu menu-heading">
                        <div class="heading"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-minus"><line x1="5" y1="12" x2="19" y2="12"></line></svg><span>Kurum Yönetimi</span></div>
                    </li>

                    <li class="menu" data-module="grup">
                        <a href="modules/grup/grup_seans.php" aria-expanded="false" class="dropdown-toggle">
                            <div class="">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-calendar">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <span>Grup & Seans</span>
                            </div>
                        </a>
                    </li>

                    

                    <li class="menu" data-module="kurum_takvim">
                        <a href="modules/kurum/kurum_takvimi.php" aria-expanded="false" class="dropdown-toggle">
                            <div class="">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-calendar">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <span>Kurum Takvimi</span>
                            </div>
                        </a>
                    </li>

                    <li class="menu" data-module="kurum_ayar">
                        <a href="#kurum_ayar_menu" data-bs-toggle="dropdown" aria-expanded="false" class="dropdown-toggle">
                            <div class="">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-settings">
                                    <circle cx="12" cy="12" r="3"></circle>
                                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82-.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                                </svg>
                                <span>Kurum Ayarları</span>
                            </div>
                            <div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </div>
                        </a>
                        <ul class="dropdown-menu submenu list-unstyled" id="kurum_ayar_menu" data-bs-parent="#vuiinMenu">
                            <li>
                                <a href="modules/kurum/kurum_sube.php">Kurum & Şube</a>
                            </li>
                            <li>
                                <a href="modules/egitmen/egitmen_listesi.php">Eğitmenler</a>
                            </li>
                            <li>
                                <a href="modules/kurum/kurum_alanlari.php">Kurum Alanları</a>
                            </li>
                            <li>
                                <a href="modules/rol/rol_yetki.php">Rol & Yetki</a>
                            </li>
                        </ul>
                    </li>

                    <li class="menu" data-module="muhasebe">
                        <a href="#muhasebe_menu" data-bs-toggle="dropdown" aria-expanded="false" class="dropdown-toggle">
                            <div class="">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-dollar-sign">
                                    <line x1="12" y1="1" x2="12" y2="23"></line>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>
                                <span>Muhasebe</span>
                            </div>
                            <div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </div>
                        </a>
                        <ul class="dropdown-menu submenu list-unstyled" id="muhasebe_menu" data-bs-parent="#vuiinMenu">
                            <li>
                                <a href="modules/muhasebe/on_muhasebe.php">Ön Muhasebe</a>
                            </li>
                            <li>
                                <a href="modules/muhasebe/tahsilatlar.php">Tahsilatlar</a>
                            </li>
                            <li>
                                <a href="modules/muhasebe/bekleyen_tahsilatlar.php">Bekleyen Tahsilatlar</a>
                            </li>
                            <li>
                                <a href="modules/muhasebe/bakiye_hareketleri.php">Bakiye Hareketleri</a>
                            </li>
                        </ul>
                    </li>

                    <li class="menu" data-module="materyal">
                        <a href="modules/materyal/materyal_havuzu.php" aria-expanded="false" class="dropdown-toggle">
                            <div class="">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-file-text">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <polyline points="10 9 9 9 8 9"></polyline>
                                </svg>
                                <span>Materyal Havuzu</span>
                            </div>
                        </a>
                    </li>

                    <li class="menu" data-module="rapor">
                        <a href="modules/rapor/aylik_raporlar.php" aria-expanded="false" class="dropdown-toggle">
                            <div class="">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-bar-chart-2">
                                    <line x1="18" y1="20" x2="18" y2="10"></line>
                                    <line x1="12" y1="20" x2="12" y2="4"></line>
                                    <line x1="6" y1="20" x2="6" y2="14"></line>
                                </svg>
                                <span>Raporlar</span>
                            </div>
                        </a>
                    </li>
                </ul>

            </nav>

        </div>
        <!--  END SIDEBAR  -->
