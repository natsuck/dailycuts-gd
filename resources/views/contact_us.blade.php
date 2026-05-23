@extends('maindesign')

@section('contact_us')

<section class="contact_section ">
    <div class="container px-0">
      <div class="heading_container ">
        <h2 class="">
          Become A Reseller
        </h2>
      </div>
    </div>
    <div class="container container-bg">
      @if(session('resellerMessage'))
        <div class="alert alert-success mt-3">
          {{ session('resellerMessage') }}
        </div>
      @endif

      <div class="row">
        <div class="col-lg-7 col-md-6 px-0">
          <div class="map_container">
            <div class="map-responsive">
              <iframe src="https://www.google.com/maps/embed/v1/place?key=AIzaSyA0s1a7phLN0iaD6-UE7m4qP-z21pH0eSc&q=PHASE+10+Wellington+Place" width="600" height="300" frameborder="0" style="border:0; width: 100%; height:100%" allowfullscreen></iframe>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-lg-5 px-0">
          <form action="{{ route('contact_us.submit') }}" method="POST" class="p-3 p-md-4">
            @csrf
            <div class="mb-3">
              <input type="text" name="name" class="form-control form-control-lg" placeholder="Name" value="{{ old('name') }}" required />
            </div>
            <div class="mb-3">
              <input type="email" name="email" class="form-control form-control-lg" placeholder="Email" value="{{ old('email') }}" required />
            </div>
            <div class="mb-3">
              <input type="tel" name="phone" class="form-control form-control-lg" placeholder="Phone" value="{{ old('phone') }}" />
            </div>
            <div class="mb-3">
              <textarea name="message" class="form-control form-control-lg" rows="4" placeholder="Message" style="min-height: 120px;" required>{{ old('message') }}</textarea>
            </div>
            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-lg" style="background-color: #D2042D; color: white;">
                SEND
              </button>
            </div>
          </form>
        </div>
        </div>
      </div>
    </div>
  </section>
  
@endsection
