@extends('maindesign')

@section('content')

@php $storeLocation = \App\Models\StoreLocation::activePickup(); @endphp

<main class="max-w-container-max mx-auto px-4 md:px-margin-desktop py-8 md:py-12">
  <div class="max-w-3xl mx-auto">

    <h1 class="font-display-lg text-display-lg-mobile md:text-display-lg text-on-surface mb-2">Privacy Policy</h1>
    <p class="font-body-md text-on-surface-variant mb-8">Last updated: {{ date('F d, Y') }}</p>

    <div class="prose prose-on-surface max-w-none font-body-md text-body-md space-y-8">

      <section>
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-3">1. Introduction</h2>
        <p class="text-on-surface-variant">Welcome to The Daily Cuts by GD (&ldquo;we,&rdquo; &ldquo;us,&rdquo; or &ldquo;our&rdquo;). We operate the website thedailycuts.com and are committed to protecting your personal information and your right to privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website and purchase our fresh meat products.</p>
      </section>

      <section>
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-3">2. Information We Collect</h2>
        <p class="text-on-surface-variant mb-3">We may collect the following types of information:</p>
        <ul class="list-disc list-inside text-on-surface-variant space-y-2 ml-4">
          <li><strong>Personal Identification Information:</strong> Name, email address, phone number, and delivery address when you place an order or create an account.</li>
          <li><strong>Payment Information:</strong> Payment transactions are processed through PayMongo. We do not store your credit card or GCash details on our servers.</li>
          <li><strong>Order History:</strong> Details of products you have purchased, order dates, and delivery statuses.</li>
          <li><strong>Usage Data:</strong> IP address, browser type, pages visited, time spent on pages, and other diagnostic data collected automatically when you access our website.</li>
          <li><strong>Cookies:</strong> We use cookies and similar tracking technologies to maintain your session and remember your preferences.</li>
        </ul>
      </section>

      <section>
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-3">3. How We Use Your Information</h2>
        <p class="text-on-surface-variant mb-3">We use the information we collect to:</p>
        <ul class="list-disc list-inside text-on-surface-variant space-y-2 ml-4">
          <li>Process and fulfill your orders, including delivery of fresh meat products.</li>
          <li>Send order confirmations, shipping notifications, and delivery updates via email or SMS.</li>
          <li>Manage your account, including authentication and profile management.</li>
          <li>Communicate with you about promotions, new products, and special offers (with your consent).</li>
          <li>Improve our website, products, and customer service.</li>
          <li>Detect and prevent fraud, unauthorized transactions, and other security issues.</li>
          <li>Comply with legal obligations under Philippine law.</li>
        </ul>
      </section>

      <section>
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-3">4. Sharing Your Information</h2>
        <p class="text-on-surface-variant mb-3">We do not sell your personal information. We may share your data with:</p>
        <ul class="list-disc list-inside text-on-surface-variant space-y-2 ml-4">
          <li><strong>Payment Processors:</strong> PayMongo, to securely handle your payment transactions.</li>
          <li><strong>Delivery Partners:</strong> Third-party logistics providers who deliver your orders to your specified address.</li>
          <li><strong>Service Providers:</strong> Hosting, analytics, and email service providers that help us operate our website.</li>
          <li><strong>Legal Authorities:</strong> When required by law, court order, or government regulation.</li>
        </ul>
      </section>

      <section>
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-3">5. Data Security</h2>
        <p class="text-on-surface-variant">We implement industry-standard security measures including SSL encryption, secure database storage, and access controls to protect your personal information. However, no method of transmission over the Internet or electronic storage is 100% secure, and we cannot guarantee absolute security.</p>
      </section>

      <section>
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-3">6. Data Retention</h2>
        <p class="text-on-surface-variant">We retain your personal information for as long as your account is active or as needed to provide you services. Order records are retained for a minimum of five (5) years in compliance with Philippine taxation and business regulations. You may request deletion of your account and personal data by contacting us.</p>
      </section>

      <section>
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-3">7. Your Rights</h2>
        <p class="text-on-surface-variant mb-3">Under the Data Privacy Act of 2012 (Republic Act No. 10173), you have the right to:</p>
        <ul class="list-disc list-inside text-on-surface-variant space-y-2 ml-4">
          <li>Be informed about how your personal data is collected and processed.</li>
          <li>Access the personal data we hold about you.</li>
          <li>Request correction of inaccurate or incomplete data.</li>
          <li>Request deletion of your personal data, subject to legal retention requirements.</li>
          <li>Object to the processing of your personal data for certain purposes.</li>
          <li Lodge a complaint with the National Privacy Commission (NPC) if you believe your rights have been violated.</li>
        </ul>
      </section>

      <section>
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-3">8. Cookies Policy</h2>
        <p class="text-on-surface-variant">Our website uses essential cookies to maintain your shopping session and remember items in your cart. We may also use analytics cookies to understand how visitors interact with our website. You can control cookie settings through your browser preferences.</p>
      </section>

      <section>
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-3">9. Children&rsquo;s Privacy</h2>
        <p class="text-on-surface-variant">Our services are not directed to individuals under the age of 18. We do not knowingly collect personal information from children. If you are a parent or guardian and believe your child has provided us with personal information, please contact us immediately.</p>
      </section>

      <section>
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-3">10. Changes to This Policy</h2>
        <p class="text-on-surface-variant">We may update this Privacy Policy from time to time. We will notify you of any material changes by posting the new policy on this page and updating the &ldquo;Last updated&rdquo; date. Your continued use of our website after any changes constitutes acceptance of the updated policy.</p>
      </section>

      <section>
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-3">11. Contact Us</h2>
        <p class="text-on-surface-variant">If you have any questions about this Privacy Policy or wish to exercise your data rights, please contact us:</p>
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
