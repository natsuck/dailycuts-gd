@extends('maindesign')

@section('content')

@php $storeLocation = \App\Models\StoreLocation::activePickup(); @endphp

<main class="max-w-container-max mx-auto px-4 md:px-margin-desktop py-8 md:py-12">
  <div class="max-w-3xl mx-auto">

    <h1 class="font-display-lg text-display-lg-mobile md:text-display-lg text-on-surface mb-2">Terms and Conditions</h1>
    <p class="font-body-md text-on-surface-variant mb-8">Last updated: {{ date('F d, Y') }}</p>

    <div class="prose prose-on-surface max-w-none font-body-md text-body-md space-y-8">

      <section>
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-3">1. Acceptance of Terms</h2>
        <p class="text-on-surface-variant">By accessing and using the The Daily Cuts by GD website (&ldquo;Website&rdquo;) and its services, you agree to be bound by these Terms and Conditions. If you do not agree to these terms, please do not use our Website or place any orders.</p>
      </section>

      <section>
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-3">2. Products and Services</h2>
        <p class="text-on-surface-variant mb-3">The Daily Cuts by GD is a fresh meat ecommerce store specializing in premium cuts of beef, pork, and chicken. All products are sourced from trusted local suppliers.</p>
        <ul class="list-disc list-inside text-on-surface-variant space-y-2 ml-4">
          <li><strong>Freshness Guarantee:</strong> All meat products are freshly cut and temperature-controlled during storage and delivery.</li>
          <li><strong>Product Images:</strong> Product images on the Website are for illustration purposes. Actual products may vary slightly in appearance.</li>
          <li><strong>Availability:</strong> All products are subject to availability. We reserve the right to discontinue any product at any time without prior notice.</li>
          <li><strong>Pricing:</strong> All prices are displayed in Philippine Pesos (&#8369;) and are inclusive of applicable taxes unless stated otherwise.</li>
        </ul>
      </section>

      <section>
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-3">3. Orders and Payment</h2>
        <ul class="list-disc list-inside text-on-surface-variant space-y-2 ml-4">
          <li>By placing an order, you are making an offer to purchase products subject to these terms.</li>
          <li>Orders are confirmed only after successful payment through our payment gateway (Maya). Supported payment methods include Maya Wallet and credit/debit cards.</li>
          <li>We reserve the right to cancel or refuse any order for any reason, including but not limited to product unavailability, pricing errors, or suspected fraudulent activity.</li>
          <li>An order confirmation will be sent to your registered email address upon successful payment.</li>
          <li>If an order is cancelled due to stock unavailability or payment issues, a full refund will be processed within 7-14 business days.</li>
        </ul>
      </section>

      <section>
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-3">4. Delivery</h2>
        <ul class="list-disc list-inside text-on-surface-variant space-y-2 ml-4">
          <li>We currently deliver within Metro Manila and select nearby areas.</li>
          <li>Delivery fees are calculated at checkout based on your delivery address and order total.</li>
          <li>Estimated delivery times are provided at checkout and are estimates only. Actual delivery times may vary due to traffic, weather, or other unforeseen circumstances.</li>
          <li>Temperature-controlled delivery is provided for all meat orders to ensure product freshness and safety.</li>
          <li>Please ensure someone is available to receive the order at the delivery address. We are not responsible for product quality issues once the order has been delivered and left unattended.</li>
          <li>If you are unavailable at the time of delivery, our delivery partner will attempt to contact you. Failed deliveries may result in additional delivery charges.</li>
        </ul>
      </section>

      <section>
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-3">5. Returns and Refunds</h2>
        <ul class="list-disc list-inside text-on-surface-variant space-y-2 ml-4">
          <li><strong>Damaged or Defective Products:</strong> If you receive damaged or defective products, please contact us within 24 hours of delivery with photographic evidence. We will arrange for a replacement or full refund.</li>
          <li><strong>Wrong Items:</strong> If you receive items that do not match your order, contact us within 24 hours and we will correct the error at no additional cost.</li>
          <li><strong>Change of Mind:</strong> Due to the perishable nature of our products, we do not accept returns for change of mind. All sales of delivered fresh meat products are final.</li>
          <li>Refunds will be processed through the original payment method within 7-14 business days upon approval.</li>
        </ul>
      </section>

      <section>
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-3">6. User Accounts</h2>
        <ul class="list-disc list-inside text-on-surface-variant space-y-2 ml-4">
          <li>You are responsible for maintaining the confidentiality of your account credentials.</li>
          <li>You agree to provide accurate and complete information during registration and to update it as necessary.</li>
          <li>You are responsible for all activities that occur under your account.</li>
          <li>We reserve the right to suspend or terminate accounts that violate these terms or engage in fraudulent activity.</li>
          <li>You may delete your account at any time through your Profile settings. Account deletion is permanent and cannot be undone.</li>
        </ul>
      </section>

      <section>
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-3">7. Intellectual Property</h2>
        <p class="text-on-surface-variant">All content on this Website, including but not limited to text, graphics, logos, images, product descriptions, and software, is the property of The Daily Cuts by GD and is protected by Philippine and international intellectual property laws. You may not reproduce, distribute, or create derivative works without our express written permission.</p>
      </section>

      <section>
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-3">8. Limitation of Liability</h2>
        <p class="text-on-surface-variant">To the maximum extent permitted by Philippine law, The Daily Cuts by GD shall not be liable for any indirect, incidental, special, consequential, or punitive damages arising out of or related to your use of our Website or purchase of our products. Our total liability shall not exceed the total amount paid by you for the specific order giving rise to the claim.</p>
      </section>

      <section>
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-3">9. Food Safety and Allergens</h2>
        <ul class="list-disc list-inside text-on-surface-variant space-y-2 ml-4">
          <li>All products are handled in accordance with food safety standards and regulations.</li>
          <li>Customers with food allergies or specific dietary requirements are advised to contact us before placing an order.</li>
          <li>Product packaging may include handling and storage instructions. Please follow these instructions to ensure product safety and quality.</li>
          <li>Raw meat products must be stored at proper refrigeration temperatures and cooked thoroughly before consumption.</li>
        </ul>
      </section>

      <section>
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-3">10. Privacy</h2>
        <p class="text-on-surface-variant">Your use of our Website is also governed by our <a href="{{ route('privacy.policy') }}" class="text-primary hover:underline font-bold">Privacy Policy</a>, which is incorporated into these Terms by reference. Please review it to understand our practices regarding your personal data.</p>
      </section>

      <section>
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-3">11. Governing Law</h2>
        <p class="text-on-surface-variant">These Terms and Conditions shall be governed by and construed in accordance with the laws of the Republic of the Philippines. Any disputes arising from these terms shall be subject to the exclusive jurisdiction of the courts of the Philippines.</p>
      </section>

      <section>
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-3">12. Changes to Terms</h2>
        <p class="text-on-surface-variant">We reserve the right to modify these Terms and Conditions at any time. Changes will be effective immediately upon posting on this page. Your continued use of the Website after any modifications constitutes acceptance of the updated terms.</p>
      </section>

      <section>
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-3">13. Contact Us</h2>
        <p class="text-on-surface-variant">For any questions or concerns regarding these Terms and Conditions, please contact us:</p>
        <ul class="list-disc list-inside text-on-surface-variant space-y-2 ml-4">
          <li>Email: <a href="mailto:nathanielquirino3210@gmail.com" class="text-primary hover:underline">nathanielquirino3210@gmail.com</a></li>
          <li>Phone: <a href="tel:+631234567891" class="text-primary hover:underline">+63 1234567891</a></li>
          <li>Address: {{ $storeLocation?->fullAddress() ?? 'Phase 10, Wellington Place' }}</li>
        </ul>
      </section>

    </div>
  </div>
</main>

@endsection
