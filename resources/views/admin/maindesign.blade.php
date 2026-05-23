<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('page_title', 'Admin Dashboard') | The Daily Cuts by GD</title>
    <meta name="description" content="The Daily Cuts by GD admin dashboard">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet" href="{{ asset('admin/vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/vendor/font-awesome/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/css/font.css') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Muli:300,400,700">
    <link rel="stylesheet" href="{{ asset('admin/css/style.default.css') }}" id="theme-stylesheet">
    <link rel="stylesheet" href="{{ asset('admin/css/custom.css') }}">
    <link rel="shortcut icon" href="{{ asset('admin/img/favicon.ico') }}">
    @stack('styles')
  </head>
  <body class="admin-shell">
    <header class="header admin-topbar">
      <nav class="navbar navbar-expand-lg">
        <div class="container-fluid d-flex align-items-center justify-content-between">
          <div class="navbar-header d-flex align-items-center">
            <a href="{{ route('dashboard') }}" class="navbar-brand admin-brand">
              <div class="brand-text brand-big visible">
                <strong class="text-primary">The Daily Cuts</strong><strong> by GD</strong>
              </div>
              <div class="brand-text brand-sm"><strong class="text-primary">GD</strong></div>
            </a>
            <button class="sidebar-toggle admin-sidebar-toggle" type="button" aria-label="Toggle sidebar">
              <i class="fa fa-long-arrow-left"></i>
            </button>
          </div>

          <div class="right-menu list-inline no-margin-bottom admin-topbar-actions">
            <a href="{{ route('index') }}" class="btn btn-outline-primary btn-sm">
              <i class="fa fa-external-link mr-1"></i> Storefront
            </a>

            <form method="POST" action="{{ route('logout') }}" class="d-inline-block mb-0">
              @csrf
              <button type="submit" class="btn btn-primary btn-sm">
                <i class="fa fa-sign-out mr-1"></i> Log Out
              </button>
            </form>
          </div>
        </div>
      </nav>
    </header>

    <div class="d-flex align-items-stretch admin-frame">
      <nav id="sidebar" class="admin-sidebar">
        <div class="sidebar-header d-flex align-items-center">
          <div class="avatar">
            <img src="{{ asset('frontend/images/img3.jpg') }}" alt="Admin profile" class="img-fluid rounded-circle">
          </div>
          <div class="title">
            <h1 class="h5">Admin</h1>
            <p>{{ Auth::user()->name ?? 'Store Manager' }}</p>
          </div>
        </div>

        <span class="heading">Operations</span>
        <ul class="list-unstyled">
          <li class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <a href="{{ route('dashboard') }}"><i class="fa fa-dashboard"></i>Dashboard</a>
          </li>

          <li class="{{ request()->routeIs('admin.vieworders') || request()->routeIs('admin.order.*') ? 'active' : '' }}">
            <a href="{{ route('admin.vieworders') }}"><i class="fa fa-shopping-bag"></i>Orders</a>
          </li>

          <li class="{{ request()->routeIs('admin.simulation.*') ? 'active' : '' }}">
            <a href="{{ route('admin.simulation.dashboard') }}"><i class="fa fa-line-chart"></i>Simulation</a>
          </li>

          <li class="{{ request()->routeIs('admin.sale-banners.*') ? 'active' : '' }}">
            <a href="{{ route('admin.sale-banners.index') }}"><i class="fa fa-bullhorn"></i>Sale Banners</a>
          </li>
        </ul>

        <span class="heading">Catalog</span>
        <ul class="list-unstyled">
          <li class="{{ request()->routeIs('admin.addproduct') || request()->routeIs('admin.viewproduct') || request()->routeIs('admin.updateproduct') || request()->routeIs('admin.searchproduct') ? 'active' : '' }}">
            <a href="#productDropdown" aria-expanded="{{ request()->routeIs('admin.addproduct') || request()->routeIs('admin.viewproduct') || request()->routeIs('admin.updateproduct') || request()->routeIs('admin.searchproduct') ? 'true' : 'false' }}" data-toggle="collapse">
              <i class="fa fa-cubes"></i>Products
            </a>
            <ul id="productDropdown" class="collapse list-unstyled {{ request()->routeIs('admin.addproduct') || request()->routeIs('admin.viewproduct') || request()->routeIs('admin.updateproduct') || request()->routeIs('admin.searchproduct') ? 'show' : '' }}">
              <li><a href="{{ route('admin.addproduct') }}">Add Product</a></li>
              <li><a href="{{ route('admin.viewproduct') }}">View Products</a></li>
            </ul>
          </li>

          <li class="{{ request()->routeIs('admin.addcategory') || request()->routeIs('admin.viewcategory') || request()->routeIs('admin.categoryupdate') ? 'active' : '' }}">
            <a href="#categoryDropdown" aria-expanded="{{ request()->routeIs('admin.addcategory') || request()->routeIs('admin.viewcategory') || request()->routeIs('admin.categoryupdate') ? 'true' : 'false' }}" data-toggle="collapse">
              <i class="fa fa-tags"></i>Categories
            </a>
            <ul id="categoryDropdown" class="collapse list-unstyled {{ request()->routeIs('admin.addcategory') || request()->routeIs('admin.viewcategory') || request()->routeIs('admin.categoryupdate') ? 'show' : '' }}">
              <li><a href="{{ route('admin.addcategory') }}">Add Category</a></li>
              <li><a href="{{ route('admin.viewcategory') }}">View Categories</a></li>
            </ul>
          </li>
        </ul>
      </nav>

      <div class="page-content admin-content">
        <div class="page-header admin-page-header">
          <div class="container-fluid d-flex flex-wrap align-items-center justify-content-between">
            <div>
              <h2 class="h5 no-margin-bottom">@yield('page_header', 'Admin Dashboard')</h2>
              <p class="admin-page-subtitle mb-0">@yield('page_subtitle', 'Manage store operations, inventory, orders, and analytics.')</p>
            </div>

            <div class="admin-page-actions">
              @yield('page_actions')
            </div>
          </div>
        </div>

        <section class="admin-page-body">
          @yield('dashboard')
          @yield('add_category')
          @yield('view_category')
          @yield('update_category')
          @yield('add_product')
          @yield('view_product')
          @yield('update_product')
          @yield('vieworders')
          @yield('delivered')
          @yield('shipped')
        </section>

        <footer class="footer admin-footer">
          <div class="footer__block block no-margin-bottom">
            <div class="container-fluid text-center">
              <p class="no-margin-bottom">2026 &copy; The Daily Cuts by GD</p>
            </div>
          </div>
        </footer>
      </div>
    </div>

    <script src="{{ asset('admin/vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('admin/vendor/popper.js/umd/popper.min.js') }}"></script>
    <script src="{{ asset('admin/vendor/bootstrap/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('admin/vendor/jquery.cookie/jquery.cookie.js') }}"></script>
    <script src="{{ asset('admin/vendor/chart.js/Chart.min.js') }}"></script>
    <script src="{{ asset('admin/vendor/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('admin/js/charts-home.js') }}"></script>
    <script src="{{ asset('admin/js/front.js') }}"></script>
    @stack('scripts')
  </body>
</html>
