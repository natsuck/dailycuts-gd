
<!DOCTYPE html>
<html>
<head>
  <!-- Basic -->
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <!-- Mobile Metas -->
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <!-- Site Metas -->
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="keywords" content="Fresh Meat, Daily Cuts, Premium Beef, Pork, Chicken, Pasay City" />
  <meta name="description" content="The Daily Cuts by GD - Farm-Fresh Meat Cut Daily, Delivered Fresh. Premium beef, pork, and chicken - clean, safe, and affordable." />
  <meta name="author" content="The Daily Cuts by GD" />
  <link rel="shortcut icon" href="{{ asset('frontend/images/img3.jpg') }}">
  <title>
    thedailycutsbygd.com
  </title>
  <!-- slider stylesheet -->
  <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" />

  <!-- bootstrap core css -->
  <link rel="stylesheet" type="text/css" href="frontend/css/bootstrap.css" />

  <!-- Custom styles for this template -->
  <link href="frontend/css/style.css" rel="stylesheet" />
  <!-- responsive style -->
  <link href="frontend/css/responsive.css" rel="stylesheet" />
</head>


<body>


  @auth
  @if(!Auth::user()->hasVerifiedEmail())

  <div id="verifyBanner" class="verify-banner">

    <div class="verify-content">
      <span>
          Please verify your email to unlock to continue shopping..
      </span>

      <!-- Resend -->
      <form method="POST" action="{{ route('verification.send') }}">
          @csrf
          <button type="submit" class="verify-btn">
              Resend Verification Email
          </button>
      </form>
      </form>

      <!-- Close -->
      <button onclick="closeVerifyBanner()" class="verify-close">
        ✕
      </button>
    </div>

  </div>

  @endif
  @endauth




  <div id="overlay" class="overlay" onclick="closeNav()"></div>

  <div class="mobile-cart-bar d-md-none">
  <div class="cart-content">
    <span class="cart-text">View Cart</span>
    <a href="{{ route('checkout') }}" class="btn cart-btn">Checkout</a>
  </div>
</div>

  <div class="bottom-nav d-md-none">

    <a href="{{ route('index') }}" class="nav-item {{ request()->routeIs('index') ? 'active' : '' }}">
      <i class="fa fa-home"></i>
      <span>Home</span>
    </a>

    <a href="{{ route('shop') }}" class="nav-item {{ request()->routeIs('shop') ? 'active' : '' }}">
      <i class="fa fa-shopping-bag"></i>
      <span>Shop</span>
    </a>

    <a href="{{ route('viewcart') }}" class="nav-item position-relative {{ request()->routeIs('viewcart') ? 'active' : '' }}">
      <i class="fa fa-shopping-cart"></i>

      @if($cartCount > 0)
        <span class="cart-badge">{{ $cartCount }}</span>
      @endif

      <span>Cart</span>
    </a>

    <a href="javascript:void(0)" onclick="openAccountNav()" class="nav-item">
      <i class="fa fa-user"></i>
      <span>Account</span>
    </a>

  </div>



  <!-- Side Navigation Drawer -->
<div id="sideNav" class="side-nav">

  <span class="close-btn" onclick="closeNav()">&times;</span>

  <h5 class="px-3 text-white mb-3">My Account</h5>

  @auth
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <a href="{{ route('checkout') }}">My Orders</a>
    <a href="{{ route('profile.edit') }}">Account Details</a>

    @if(Auth::user()->user_type == 'admin')
      <a href="{{ route('dashboard') }}">Admin Panel</a>
    @endif

    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button type="submit" class="logout-btn">Logout</button>
    </form>
  @else
    <a href="{{ route('login') }}">Login</a>
    <a href="{{ route('register') }}">Sign Up</a>
  @endauth

  <hr style="border-color: rgba(255,255,255,0.3)">

  <a href="{{ route('index') }}">Home</a>
  <a href="{{ route('shop') }}">Shop</a>
  <a href="{{ route('contact_us') }}">Become A Reseller</a>

</div>

<div class="mobile-header d-md-none">
  <!-- CENTER: shop name -->
  <h5 class="shop-title">The Daily Cuts by GD</h5>
</div>

  <div class="hero_area">
    <!-- header section strats -->
    <header class="header_section">
      <nav class="navbar navbar-expand-lg custom_nav-container ">
        <div class="d-flex align-items-center w-100 justify-content-between">


        <div class="collapse navbar-collapse" id="navbarSupportedContent">
          <ul class="navbar-nav  ">
            <li class="nav-item {{ request()->routeIs('index') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('index') }}">Home</a>
            </li>
            <li class="nav-item {{ request()->routeIs('shop') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('shop') }}">Shop</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#">
                Why Us
              </a>
            </li>
            <li class="nav-item {{ request()->routeIs('contact_us') ? 'active' : '' }}">
              <a class="nav-link" href="{{ route('contact_us') }}">Become A Reseller</a>
            </li>
          </ul>
          <div class="user_option">
            @if(Auth::check())
            <div class="dropdown d-none d-md-inline-flex align-items-center">
              <!-- Trigger -->
              <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fa fa-user" aria-hidden="true"></i>
                <span>{{ Auth::user()->name }}</span>
              </a>

              <!-- Dropdown menu -->
              <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                <a class="dropdown-item" href="{{ route('profile.edit') }}">Profile</a>
                <a class="dropdown-item" href="{{ route('checkout') }}">My Orders</a>
                  @if(Auth::user()->user_type == 'admin')
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger fw-bold" href="{{ route('dashboard') }}">
                      <i class="fa fa-cogs"></i> Admin Panel
                    </a>
                  @endif
                <div class="dropdown-divider"></div>
                <form method="POST" action="{{ route('logout') }}">
                  @csrf
                  <button type="submit" class="dropdown-item text-danger">
                    <i class="fa fa-sign-out-alt"></i> Logout
                  </button>
                </form>
              </div>
            </div>
            @else
            <a href="{{route('login')}}" >
              <i class="fa fa-user" aria-hidden="true"></i>
              <span>
                Login
              </span>
            </a>
            <a href="{{route('register')}}">
              <i class="fa fa-user" aria-hidden="true"></i>
              <span>
                Sign Up
              </span>
            </a>     
            @endif      
            <a href="{{ route('viewcart') }}" class="position-relative">
              <i class="fa fa-shopping-bag" aria-hidden="true">{{$cartCount}}</i>
            </a>
            
            <form class="form-inline ">
              <button class="btn nav_search-btn" type="submit">
                <i class="fa fa-search" aria-hidden="true"></i>
              </button>
            </form>
          </div>
        </div>
      </nav>
    </header>
    <!-- end header section -->
    @if(isset($activeSaleBanners) && $activeSaleBanners->isNotEmpty())
      <section class="sale_banner_section">
        <div class="container">
          <div id="saleBannerCarousel" class="carousel slide" data-ride="carousel">
            <div class="carousel-inner">
              @foreach($activeSaleBanners as $banner)
                <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                  <div class="sale-banner" style="background-color: {{ $banner->background_color }}; color: {{ $banner->text_color }};">
                    <div class="sale-banner-copy">
                      @if($banner->badge_text)
                        <span class="sale-banner-badge">{{ $banner->badge_text }}</span>
                      @endif

                      <h2>{{ $banner->title }}</h2>

                      @if($banner->subtitle)
                        <p>{{ $banner->subtitle }}</p>
                      @endif

                      @if($banner->button_text && $banner->button_url)
                        <a href="{{ $banner->button_url }}" class="sale-banner-btn">
                          {{ $banner->button_text }}
                        </a>
                      @endif
                    </div>

                    @if($banner->image_path)
                      <div class="sale-banner-media">
                        <img src="{{ asset($banner->image_path) }}" alt="{{ $banner->title }}">
                      </div>
                    @endif
                  </div>
                </div>
              @endforeach
            </div>

            @if($activeSaleBanners->count() > 1)
              <a class="carousel-control-prev" href="#saleBannerCarousel" role="button" data-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="sr-only">Previous</span>
              </a>
              <a class="carousel-control-next" href="#saleBannerCarousel" role="button" data-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="sr-only">Next</span>
              </a>
            @endif
          </div>
        </div>
      </section>
    @endif

    <!-- slider section -->
    <section class="slider_section">
      <div class="slider_container">
        <div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel">
          <div class="carousel-inner">
            <div class="carousel-item active">
              <div class="container-fluid">
                <div class="row">
                  <div class="col-md-7 d-flex flex-column justify-content-center align-items-end">
                  <div class="detail-box"> 
                    <h1 class="display-5 mb-3" >
                      Farm-Fresh Meat <br>
                      Cut Daily, Delivered Fresh
                    </h1>

                    <p class="lead mb-4">
                      Premium beef, pork, and chicken — clean, safe, and affordable for every home.
                    </p>

                    <div class="btn-box mb-3">
                      <a href="{{ route('shop') }}" class="btn-1 btn btn-lg">Shop Now</a>
                    </div>
                  <div class="trust-badges mt-3 d-flex flex-column flex-md-row gap-4">
                    <span class="text-white px-4">✔ 100% Fresh</span> 
                    <span class="text-white px-4">✔ Locally Sourced</span>
                    <span class="text-white px-4">✔ Same-Day Delivery</span>
                  </div>
                  </div>
                  </div>
                  <div class="col-md-5 d-flex align-items-center justify-content-center">
                    <div class="img-box w-100">
                      <img src="frontend/images/img3.jpg" class="img-fluid" alt="Fresh Meat" loading="lazy" />
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
          </div>
          
        </div>
      </div>
    </section>
    <!-- end slider section -->
  </div>
  <!-- end hero area -->
  <!-- shop section -->
        <section class="shop_section layout_padding">
          @yield('dashboard')
            @yield('index')
            @yield('product_details')
            @yield('contact_us')
            @yield('shop')
            @yield('login')
            @yield('register')
            @yield('viewcart')
            @yield('checkout')
            @yield('content')
            @yield('profile')
            @yield('verify')

        </section>
  <!-- end shop section -->
  <!-- contact section -->
  <!-- end contact section -->
  <!-- info section -->

  <section class="info_section  layout_padding2-top">
    <div class="social_container">
      <div class="social_box">
        <a href="https://www.facebook.com/profile.php?id=61581799587385" target="_blank" rel="noopener noreferrer">
          <i class="fa fa-facebook" aria-hidden="true"></i>
        </a>
        <a href="https://www.instagram.com/thedailycutbygd/" target="_blank" rel="noopener noreferrer">
          <i class="fa fa-instagram" aria-hidden="true"></i>
        </a>
      </div>
    </div>
    <div class="info_container ">
      <div class="container">
        <div class="row">
          <div class="col-12 col-md-6 col-lg-3">
            <h6 class="mb-3">
              ABOUT US
            </h6>
            <p class="small">
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed doLorem ipsum dolor sit amet, consectetur adipiscing elit, sed doLorem ipsum dolor sit amet,
            </p>
          </div>
          <div class="col-12 col-md-6 col-lg-3">
            <div class="info_form ">
              <h5 class="mb-3">
                Newsletter
              </h5>
              <form action="#" class="d-flex flex-column gap-2">
                <input type="email" class="form-control" placeholder="Enter your email" required>
                <button class="btn btn-sm" style="background-color: #D2042D; color: white;">
                  Subscribe
                </button>
              </form>
            </div>
          </div>
          <div class="col-12 col-md-6 col-lg-3">
            <h6 class="mb-3">
              NEED HELP
            </h6>
            <p class="small">
              Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed doLorem ipsum dolor sit amet, consectetur adipiscing elit, sed doLorem ipsum dolor sit amet,
            </p>
          </div>

        <div class="col-12 col-md-6 col-lg-3">
        <h6 class="mb-3">CONTACT US</h6>

        <div class="info_link-box">
            <a href="https://www.google.com/maps/search/?api=1&query=PHASE+10+Wellington+Place" target="_blank">
            <i class="fa fa-map-marker"></i>
            <span>Find Our Stores</span>
            </a>
        </div>

        <div class="info_link-box">
            <a href="tel:+631234567891">
            <i class="fa fa-phone"></i>
            <span>+63 1234567891</span>
            </a>
            <a href="mailto:demo@gmail.com">
            <i class="fa fa-envelope"></i>
            <span>demo@gmail.com</span>
            </a>
        </div>
        </div>
        </div>
      </div>
    </div>
    <!-- footer section -->
    <footer class=" footer_section">
      <div class="container">
        <p>
          &copy; <span id="displayYear"></span> All Rights Reserved By
          <a href="https://html.design/">The Daily Cuts by GD</a>
        </p>
      </div>
    </footer>
    <!-- footer section -->

  </section>
  <!-- end info section -->

  <script src="frontend/js/jquery-3.4.1.min.js"></script>
  <script src="frontend/js/bootstrap.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js">
  
  </script>
  <script src="frontend/js/custom.js"></script>

<script>
function openAccountNav() {
  document.getElementById("sideNav").style.left = "0";
  document.getElementById("overlay").style.display = "block";
}

function closeNav() {
  document.getElementById("sideNav").style.left = "-260px";
  document.getElementById("overlay").style.display = "none";
}
</script>


<script>
document.addEventListener('DOMContentLoaded', function () {

    let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    document.querySelectorAll('.qty-box').forEach(box => {

        let input = box.querySelector('.qty-input');
        let plus = box.querySelector('.qty-plus');
        let minus = box.querySelector('.qty-minus');
        let id = box.dataset.id;

        function updateCart(qty) {
            fetch(`/cart/update/${id}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({ quantity: qty })
            })
            .then(res => res.json())
            .then(data => {
                console.log(data);

                if (data.success) {
                    input.value = data.quantity;

                    let row = box.closest('tr');
                    row.querySelector('.subtotal').innerText = '₱' + data.subtotal;
                }
            });
        }

        plus.addEventListener('click', () => {
            updateCart(parseInt(input.value) + 1);
        });

        minus.addEventListener('click', () => {
            updateCart(Math.max(1, parseInt(input.value) - 1));
        });

    });

});

</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    let snackbar = document.getElementById("cartSnackbar");

    if (snackbar) {
        // show it
        snackbar.classList.add("show");

        // hide after 2 seconds
        setTimeout(() => {
            snackbar.classList.remove("show");
        }, 2000);
    }
});
</script>

</body>

</html>
