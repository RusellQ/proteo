<!DOCTYPE html>

<html lang="es">

<head>
    @vite(['resources/js/app.js'])
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="assets/cropped-color1.png" type="image/x-icon">

    <title>@yield('title')</title>

    <!-- Custom fonts for this template-->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="assets/css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        /* ============================================================
           Paleta corporativa Proteo
           7421C #440412 · 1945C #AB0A3D · Black 7C #3D3935
           125C #B68400 · 401C #B0ABA1
           ============================================================ */
        :root {
            --bg: #f7f5f2;
            --surface: #ffffff;
            --ink: #3D3935;
            --ink-soft: #78736a;
            --line: #e4e0d8;
            --brand: #AB0A3D;
            --brand-dark: #440412;
            --brand-soft: #f7e5eb;
            --gold: #B68400;
            --gold-soft: #faf1dd;
            --stone: #B0ABA1;
        }

        body,
        html {
            font-family: 'Montserrat', sans-serif !important;
            color: var(--ink);
        }

        body {
            background-color: var(--bg);
        }

        h1, h2, h3, h4, h5, h6,
        .h1, .h2, .h3, .h4, .h5, .h6,
        .btn, .form-control, .dropdown-menu,
        .navbar, .sidebar, .card, .modal {
            font-family: 'Montserrat', sans-serif !important;
        }

        /* ---------- Sidebar ---------- */
        #accordionSidebar.sidebar {
            background: linear-gradient(180deg, var(--brand-dark) 0%, var(--brand) 100%) !important;
            overflow-x: hidden;
        }

        @media (min-width: 768px) {
            #accordionSidebar.sidebar {
                width: 16rem !important;
            }
        }

        #accordionSidebar .sidebar-brand {
            height: auto;
            padding: 1rem 0.85rem 0.85rem;
        }

        #accordionSidebar .sidebar-brand-logo {
            background: #fff;
            border-radius: 0.55rem;
            padding: 0.45rem 0.6rem;
            width: 100%;
            line-height: 0;
        }

        #accordionSidebar .sidebar-brand-logo img {
            display: block;
            width: 100%;
            height: auto;
            max-height: 52px;
            object-fit: contain;
        }

        #accordionSidebar .sidebar-divider {
            border-top: 1px solid rgba(255, 255, 255, 0.14);
            margin: 0.5rem 1rem;
        }

        #accordionSidebar .nav-item {
            position: relative;
            border-radius: 0;
        }

        #accordionSidebar .nav-item > a.nav-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0;
            padding: 0.85rem 0.2rem;
            border-radius: 0.5rem;
            margin: 0.15rem 0.45rem;
            width: auto !important;
            box-sizing: border-box;
            transition: background-color 0.18s ease;
        }

        #accordionSidebar .nav-item > a.nav-link i {
            flex: 0 0 auto;
            width: 1.1rem;
            text-align: center;
            margin-right: 0;
            color: rgba(255, 255, 255, 0.7) !important;
            transition: color 0.18s ease;
        }

        #accordionSidebar .nav-item > a.nav-link span {
            display: none;
            color: rgba(255, 255, 255, 0.92) !important;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.01em;
            line-height: 1.25;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: color 0.18s ease;
        }

        #accordionSidebar .nav-item > a.nav-link[data-toggle="collapse"]::after {
            display: none;
        }

        @media (min-width: 768px) {
            #accordionSidebar:not(.toggled) .nav-item > a.nav-link {
                flex-direction: row;
                justify-content: flex-start;
                gap: 0.6rem;
                padding: 0.75rem 0.85rem;
                margin: 0.12rem 0.65rem;
            }

            #accordionSidebar:not(.toggled) .nav-item > a.nav-link i {
                flex: 0 0 1.1rem;
            }

            #accordionSidebar:not(.toggled) .nav-item > a.nav-link span {
                display: block;
                flex: 1 1 auto;
                min-width: 0;
            }

            #accordionSidebar:not(.toggled) .nav-item > a.nav-link[data-toggle="collapse"]::after {
                display: inline-block;
                float: none;
                margin-left: auto;
                flex: 0 0 1rem;
            }

            #accordionSidebar:not(.toggled) .nav-item .collapse,
            #accordionSidebar:not(.toggled) .nav-item .collapsing {
                margin: 0 !important;
            }
        }

        #accordionSidebar .nav-item:hover > a.nav-link {
            background-color: rgba(255, 255, 255, 0.1);
        }

        #accordionSidebar .nav-item:hover > a.nav-link i,
        #accordionSidebar .nav-item:hover > a.nav-link span {
            color: #ffffff !important;
        }

        #accordionSidebar .nav-item.active {
            background-color: transparent;
        }

        #accordionSidebar .nav-item.active > a.nav-link {
            background-color: rgba(255, 255, 255, 0.14) !important;
            position: relative;
        }

        #accordionSidebar .nav-item.active > a.nav-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0.4rem;
            bottom: 0.4rem;
            width: 3px;
            border-radius: 0 2px 2px 0;
            background: var(--gold);
        }

        #accordionSidebar .nav-item.active > a.nav-link i,
        #accordionSidebar .nav-item.active > a.nav-link span,
        #accordionSidebar .nav-item.active i {
            color: #ffffff !important;
        }

        #accordionSidebar .nav-item.active:hover {
            background-color: transparent !important;
            cursor: default;
        }

        #accordionSidebar .collapse-inner,
        #accordionSidebar .collapsing .collapse-inner {
            border-radius: 0.55rem !important;
            box-shadow: none !important;
            margin: 0 0.65rem 0.45rem !important;
            padding: 0.35rem !important;
            width: auto !important;
            max-width: 100%;
        }

        #accordionSidebar .collapse-header {
            color: var(--ink-soft);
            font-weight: 700;
            font-size: 0.68rem;
            letter-spacing: 0.04em;
        }

        #accordionSidebar .collapse-item {
            border-radius: 0.4rem;
            padding: 0.5rem 0.7rem;
            margin: 0.08rem 0;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--ink-soft);
            white-space: normal !important;
            overflow-wrap: anywhere;
            line-height: 1.3;
            transition: background-color 0.15s, color 0.15s;
        }

        #accordionSidebar .collapse-item:hover,
        #accordionSidebar .collapse-item:focus {
            background-color: var(--brand-soft);
            color: var(--brand-dark);
        }

        #accordionSidebar .collapse-item.active {
            background-color: var(--brand-soft);
            color: var(--brand-dark);
            font-weight: 700;
        }

        #accordionSidebar #sidebarToggle {
            background-color: rgba(255, 255, 255, 0.14);
            color: #fff;
        }

        #accordionSidebar #sidebarToggle:hover {
            background-color: rgba(255, 255, 255, 0.24);
        }

        @media (max-width: 767.98px) {
            #accordionSidebar.sidebar {
                overflow: visible;
            }

            #accordionSidebar .sidebar-brand {
                padding: 0.85rem 0.55rem;
            }

            #accordionSidebar .sidebar-brand-logo {
                padding: 0.35rem;
            }

            #accordionSidebar .sidebar-brand-logo img {
                max-height: 28px;
            }

            #accordionSidebar .collapse-inner {
                min-width: 14rem;
                width: max-content !important;
                margin: 0.15rem 0 0.15rem 0.4rem !important;
                box-shadow: 0 8px 22px rgba(20, 10, 15, 0.22) !important;
            }

            #accordionSidebar .collapse-item {
                white-space: nowrap !important;
                overflow-wrap: normal;
            }
        }

        @media (min-width: 768px) {
            #accordionSidebar.sidebar.toggled {
                width: 6.5rem !important;
                overflow: visible;
            }

            #accordionSidebar.toggled .sidebar-brand {
                padding: 0.85rem 0.55rem;
            }

            #accordionSidebar.toggled .sidebar-brand-logo {
                padding: 0.35rem;
            }

            #accordionSidebar.toggled .sidebar-brand-logo img {
                max-height: 28px;
            }

            #accordionSidebar.toggled .nav-item > a.nav-link {
                flex-direction: column;
                justify-content: center;
                gap: 0;
                margin: 0.15rem 0.45rem;
                padding: 0.85rem 0.2rem;
            }

            #accordionSidebar.toggled .nav-item > a.nav-link span,
            #accordionSidebar.toggled .nav-item > a.nav-link[data-toggle="collapse"]::after {
                display: none;
            }

            #accordionSidebar.toggled .nav-item > a.nav-link i {
                margin-right: 0;
                font-size: 1rem;
            }

            #accordionSidebar.toggled .nav-item .collapse {
                position: absolute;
                left: 6.5rem;
                top: 0;
                margin: 0;
                z-index: 20;
            }

            #accordionSidebar.toggled .collapse-inner {
                min-width: 14rem;
                width: max-content !important;
                max-width: 18rem;
                margin: 0.15rem 0 0.15rem 0.4rem !important;
                box-shadow: 0 8px 22px rgba(20, 10, 15, 0.22) !important;
            }

            #accordionSidebar.toggled .collapse-item {
                white-space: nowrap !important;
                overflow-wrap: normal;
            }
        }

        /* ---------- Topbar ---------- */
        .topbar {
            border-bottom: 1px solid var(--line);
            box-shadow: 0 1px 2px rgba(20, 30, 60, 0.04) !important;
        }

        .topbar .navbar-brand {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--ink) !important;
            letter-spacing: -0.01em;
            margin: 0;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            text-align: center;
        }

        .topbar .nav-link {
            color: var(--ink-soft) !important;
        }

        .topbar #userDropdown i {
            color: var(--ink-soft) !important;
        }

        .dropdown-menu {
            border: 1px solid var(--line);
            border-radius: 0.7rem;
        }

        .dropdown-item:hover,
        .dropdown-item:focus {
            background-color: var(--brand-soft);
        }

        /* ---------- Botones globales ---------- */
        .btn-primary {
            background-color: var(--brand) !important;
            border-color: var(--brand) !important;
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background-color: var(--brand-dark) !important;
            border-color: var(--brand-dark) !important;
        }

        .btn-secondary {
            background-color: #fff !important;
            border-color: var(--line) !important;
            color: var(--ink) !important;
        }

        .btn-secondary:hover {
            background-color: #f5f7fb !important;
            border-color: var(--brand) !important;
        }

        .scroll-to-top {
            background-color: var(--brand);
        }

        .scroll-to-top:hover {
            background-color: var(--brand-dark);
        }

        .modal-content {
            border: 1px solid var(--line);
            border-radius: 0.75rem;
        }

        .modal-header {
            border-bottom-color: var(--line);
        }

        .modal-footer {
            border-top-color: var(--line);
        }
    </style>

</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">

        @if(Auth::check() && Auth::user()->hasRole(['administrador', 'plantillas', 'capturista', 'validador', 'carrusel', 'onepiece']))
        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center">
                <div class="sidebar-brand-logo">
                    <img src="assets/logo.png" alt="Proteo">
                </div>
            </a>

            @php
                $navIs = fn (...$patterns) => request()->is(...$patterns);
                $navPlantillas = $navIs('consultarplantillas', 'crearplantillas', 'predeterminadasplantillas');
                $navDocumentos = $navIs('consultardocumentos', 'consultardocumentosglobal', 'creardocumentos', 'validardocumentos');
                $navUsuarios = $navIs('Createuser', 'list-user', 'list-users');
            @endphp

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Dashboard -->
            <li class="nav-item {{ $navIs('dashboard') ? 'active' : '' }}">
                <a class="nav-link" href="/dashboard" title="Panel de control">
                    <i class="fas fa-home"></i>
                    <span>Panel de control</span>
                </a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            @if(Auth::check() && Auth::user()->hasRole(['plantillas', 'onepiece']))
            <li class="nav-item {{ $navPlantillas ? 'active' : '' }}">
                <a class="nav-link {{ $navPlantillas ? '' : 'collapsed' }}" href="#" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="{{ $navPlantillas ? 'true' : 'false' }}" aria-controls="collapseTwo" title="Plantillas">
                    <i class="fas fa-folder-open"></i>
                    <span>Plantillas</span>
                </a>
                <div id="collapseTwo" class="collapse {{ $navPlantillas ? 'show' : '' }}" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Acciones:</h6>
                        <a class="collapse-item {{ $navIs('consultarplantillas') ? 'active' : '' }}" href="/consultarplantillas">Consultar plantillas</a>
                        <a class="collapse-item {{ $navIs('crearplantillas') ? 'active' : '' }}" href="/crearplantillas">Crear nueva plantilla</a>
                    </div>
                </div>
            </li>
            @endif

            @if(Auth::check() && Auth::user()->hasRole(['administrador', 'onepiece']))
            <li class="nav-item {{ $navDocumentos ? 'active' : '' }}">
                <a class="nav-link {{ $navDocumentos ? '' : 'collapsed' }}" href="#" data-toggle="collapse" data-target="#collapseUtilities" aria-expanded="{{ $navDocumentos ? 'true' : 'false' }}" aria-controls="collapseUtilities" title="Documentos">
                    <i class="fas fa-file-alt"></i>
                    <span>Documentos</span>
                </a>
                <div id="collapseUtilities" class="collapse {{ $navDocumentos ? 'show' : '' }}" aria-labelledby="headingUtilities" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Acciones:</h6>
                        <a class="collapse-item {{ $navIs('consultardocumentos') ? 'active' : '' }}" href="/consultardocumentos">Consultar Documentos</a>
                        <a class="collapse-item {{ $navIs('consultardocumentosglobal') ? 'active' : '' }}" href="/consultardocumentosglobal">Busqueda global</a>
                        <a class="collapse-item {{ $navIs('validardocumentos') ? 'active' : '' }}" href="/validardocumentos">Validar documentos</a>
                        <a class="collapse-item {{ $navIs('creardocumentos') ? 'active' : '' }}" href="/creardocumentos">Crear documentos</a>
                    </div>
                </div>
            </li>
            @endif

            @if(Auth::check() && Auth::user()->hasRole(['capturista']))
            <li class="nav-item {{ $navDocumentos ? 'active' : '' }}">
                <a class="nav-link {{ $navDocumentos ? '' : 'collapsed' }}" href="#" data-toggle="collapse" data-target="#collapseUtilitiesCapturista" aria-expanded="{{ $navDocumentos ? 'true' : 'false' }}" aria-controls="collapseUtilitiesCapturista" title="Documentos">
                    <i class="fas fa-file-alt"></i>
                    <span>Documentos</span>
                </a>
                <div id="collapseUtilitiesCapturista" class="collapse {{ $navDocumentos ? 'show' : '' }}" aria-labelledby="headingUtilities" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Acciones:</h6>
                        <a class="collapse-item {{ $navIs('creardocumentos') ? 'active' : '' }}" href="/creardocumentos">Crear documentos</a>
                    </div>
                </div>
            </li>
            @endif

            @if(Auth::check() && Auth::user()->hasRole(['validador']))
            <li class="nav-item {{ $navDocumentos ? 'active' : '' }}">
                <a class="nav-link {{ $navDocumentos ? '' : 'collapsed' }}" href="#" data-toggle="collapse" data-target="#collapseUtilitiesValidador" aria-expanded="{{ $navDocumentos ? 'true' : 'false' }}" aria-controls="collapseUtilitiesValidador" title="Documentos">
                    <i class="fas fa-file-alt"></i>
                    <span>Documentos</span>
                </a>
                <div id="collapseUtilitiesValidador" class="collapse {{ $navDocumentos ? 'show' : '' }}" aria-labelledby="headingUtilities" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Acciones:</h6>
                        <a class="collapse-item {{ $navIs('validardocumentos') ? 'active' : '' }}" href="/validardocumentos">Validar documentos</a>
                    </div>
                </div>
            </li>
            @endif

            @if(Auth::check() && Auth::user()->hasRole(['carrusel', 'onepiece']))
            <li class="nav-item {{ $navIs('carrusel') ? 'active' : '' }}">
                <a class="nav-link" href="/carrusel" title="Carrusel">
                    <i class="far fa-images"></i>
                    <span>Carrusel</span>
                </a>
            </li>
            @endif

            @if(Auth::check() && Auth::user()->hasRole(['administrador', 'onepiece']))
            <li class="nav-item {{ $navIs('respaldo') ? 'active' : '' }}">
                <a class="nav-link" href="/respaldo" title="Respaldo">
                    <i class="fas fa-download"></i>
                    <span>Respaldo</span>
                </a>
            </li>
            @endif

            {{-- Estadísticas oculto de momento; se usa el dashboard de inicio
            @if(Auth::check() && Auth::user()->hasRole(['administrador', 'onepiece']))
            <li class="nav-item {{ $navIs('estadisticas') ? 'active' : '' }}">
                <a class="nav-link" href="/estadisticas" title="Estadísticas">
                    <i class="fas fa-chart-bar"></i>
                    <span>Estadisticas</span>
                </a>
            </li>
            @endif
            --}}

            @if(Auth::check() && Auth::user()->hasRole(['onepiece']))
            <li class="nav-item {{ $navIs('adminpublic') ? 'active' : '' }}">
                <a class="nav-link" href="/adminpublic" title="Vista pública">
                    <i class="fas fa-magic"></i>
                    <span>Vista publica</span>
                </a>
            </li>
            @endif

            @if(Auth::check() && Auth::user()->hasRole(['onepiece']))
            <li class="nav-item {{ $navUsuarios ? 'active' : '' }}">
                <a class="nav-link {{ $navUsuarios ? '' : 'collapsed' }}" href="#" data-toggle="collapse" data-target="#collapsePages" aria-expanded="{{ $navUsuarios ? 'true' : 'false' }}" aria-controls="collapsePages" title="Usuarios">
                    <i class="fas fa-users"></i>
                    <span>Usuarios</span>
                </a>
                <div id="collapsePages" class="collapse {{ $navUsuarios ? 'show' : '' }}" aria-labelledby="headingPages" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Acciones:</h6>
                        <a class="collapse-item {{ $navIs('Createuser') ? 'active' : '' }}" href="/Createuser">Crear usuario</a>
                        <a class="collapse-item {{ $navIs('list-user', 'list-users') ? 'active' : '' }}" href="/list-user">Listar usuarios</a>
                    </div>
                </div>
            </li>
            @endif

            <!-- Divider -->
            <hr class="sidebar-divider d-none d-md-block">

            <!-- Sidebar Toggler (Sidebar) -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>
        </ul>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow position-relative">
                    <h1 class="navbar-brand">@yield('page-title')</h1>

                    <ul class="navbar-nav ml-auto">
                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">{{ Auth::user()->nombre }} {{ Auth::user()->apellido_paterno }} {{ Auth::user()->apellido_materno }}</span>
                                <i class="fas fa-caret-down"></i>
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Cerrar sesión
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>
                <!-- End of Topbar -->
                <div id="swup">

                    <!-- Begin Page Content -->
                    <div class="container-fluid">
                        @yield('content')
                    </div>
                    <!-- End of Main Content -->

                </div>

            </div>
        </div>
        <!-- End of Content Wrapper -->

        @else

        <!-- Mostrar contenido alternativo para usuarios con rol "user" -->

        <div class="lock"></div>
        <div class="message">
            <h1>El acceso a esta pagina esta restingido</h1>
            <p>Por favor comunicate con el administrador si consideras que hay un error</p>
            <a href="http://192.169.1.92:8000/home" type="button" class="btn btn-danger">Salir</a>
        </div>

        <style>
            @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap');

            * {
                position: relative;
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-family: 'Montserrat', sans-serif;
            }

            body {
                height: 100vh;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                background: linear-gradient(135deg, #f7f5f2, #e4e0d8);
            }

            h1 {
                margin: 40px 0 20px;
                color: #3D3935;
                font-size: 1.4rem;
            }

            p {
                text-align: center;
                color: #78736a;
            }

            .lock {
                border-radius: 5px;
                width: 65px;
                height: 45px;
                background-color: #440412;
                animation: dip 1s;
                animation-delay: 1.5s;
            }

            .lock::before,
            .lock::after {
                content: '';
                position: absolute;
                border-left: 5px solid #440412;
                height: 20px;
                width: 15px;
                left: calc(50% - 12.5px);
            }

            .lock::before {
                top: -30px;
                border: 5px solid #440412;
                border-bottom-color: transparent;
                border-radius: 15px 15px 0 0;
                height: 30px;
                animation: lock 2s, spin 2s;
            }

            .lock::after {
                top: -10px;
                border-right: 5px solid transparent;
                animation: spin 2s;
            }

            .message {
                text-align: center;
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .message .btn-danger {
                margin-top: 1rem;
                background-color: #AB0A3D;
                border-color: #AB0A3D;
                font-weight: 600;
            }

            .message .btn-danger:hover {
                background-color: #440412;
                border-color: #440412;
            }

            @keyframes lock {
                0% { top: -45px; }
                65% { top: -45px; }
                100% { top: -30px; }
            }

            @keyframes spin {
                0% {
                    transform: scaleX(-1);
                    left: calc(50% - 30px);
                }
                65% {
                    transform: scaleX(1);
                    left: calc(50% - 12.5px);
                }
            }

            @keyframes dip {
                0% { transform: translateY(0px); }
                50% { transform: translateY(10px); }
                100% { transform: translateY(0px); }
            }
        </style>

        @endif
    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">¿Estás listo para irte?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Seleccione "Cerrar sesión" a continuación si está listo para finalizar su sesión actual.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancelar</button>
                    <form id="logout-form" action="{{ route('users.logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                    <button class="btn btn-primary" type="button" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Cerrar sesión</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="assets/vendor/jquery/jquery.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="assets/vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="assets/js/sb-admin-2.min.js"></script>

    <!-- Page level plugins -->
    <script src="assets/vendor/chart.js/Chart.min.js"></script>

</body>

</html>
