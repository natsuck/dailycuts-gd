@extends('maindesign')

@section('content')

@php $storeLocation = \App\Models\StoreLocation::activePickup(); @endphp

<main class="max-w-container-max mx-auto px-4 md:px-margin-desktop py-8 md:py-12">
  <div class="max-w-3xl mx-auto">

    <h1 class="font-display-lg text-display-lg-mobile md:text-display-lg text-on-surface mb-2">Store Policies</h1>
    <p class="font-body-md text-on-surface-variant mb-8">Last updated: {{ date('F d, Y') }}</p>

    <div class="prose prose-on-surface max-w-none font-body-md text-body-md space-y-8">

      <section>
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-3">1. Shipping &amp; Delivery</h2>
        <ul class="list-disc list-inside text-on-surface-variant space-y-2 ml-4">
          <li><strong>Delivery Areas:</strong> As of the moment, we can only serve Metro Manila and some parts of Luzon (Laguna, Cavite, Bulacan, and Rizal).</li>
          <li><strong>Same-Day Delivery:</strong> Yes, we offer same-day delivery. The cut-off is 3:00pm. Orders placed after 3:00pm will be shipped out the next day.</li>
          <li><strong>Delivery Partner:</strong> For your convenience, we book the delivery for you via Lalamove.</li>
          <li><strong>Operating Hours:</strong> We are open from Mondays&ndash;Sundays, 9am&ndash;6pm.</li>
          <li><strong>Delivery Fees:</strong> Delivery fees are calculated at checkout based on your delivery address and order total.</li>
          <li><strong>Handling:</strong> All meat products are temperature-controlled during storage and delivery to ensure freshness and safety.</li>
          <li>Please ensure someone is available to receive the order at the delivery address. We are not responsible for product quality issues once the order has been delivered and left unattended.</li>
        </ul>
      </section>

      <section id="refund-policy">
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-3">2. Return &amp; Refund Policy</h2>
        <p class="text-on-surface-variant mb-6">We value our customers and are committed to delivering safe, high-quality frozen products. To ensure fairness and compliance with food safety standards, please review our Return &amp; Refund Policy below.</p>

        <div class="space-y-6">
          <div>
            <h3 class="font-headline-sm text-headline-sm text-on-surface mb-2">1. No Return, No Refund on Opened Items</h3>
            <p class="text-on-surface-variant mb-2">All frozen and perishable products are final sale.</p>
            <p class="text-on-surface-variant">Once packaging has been opened, unsealed, or tampered with, the item is no longer eligible for return, exchange, or refund. This is to protect food safety, as we cannot verify handling or storage conditions after opening.</p>
          </div>

          <div>
            <h3 class="font-headline-sm text-headline-sm text-on-surface mb-2">2. Change of Mind</h3>
            <p class="text-on-surface-variant mb-2">We do not accept returns or refunds for the following reasons:</p>
            <ul class="list-disc list-inside text-on-surface-variant space-y-2 ml-4 mb-2">
              <li>Change of mind</li>
              <li>Item no longer needed</li>
              <li>Ordered by mistake</li>
              <li>Personal preference (taste, cut, size, etc.)</li>
            </ul>
            <p class="text-on-surface-variant">Customers are encouraged to review product details carefully before checkout.</p>
          </div>

          <div>
            <h3 class="font-headline-sm text-headline-sm text-on-surface mb-2">3. Failed Delivery &amp; Re-Delivery</h3>
            <p class="text-on-surface-variant mb-2">If a delivery attempt fails due to:</p>
            <ul class="list-disc list-inside text-on-surface-variant space-y-2 ml-4 mb-2">
              <li>Customer being unreachable</li>
              <li>Incorrect or incomplete address</li>
              <li>Customer not being available to receive the order</li>
            </ul>
            <p class="text-on-surface-variant mb-2">The order will be marked as delivered but not received, and refunds will not be issued. We can assist with re-delivery, subject to product availability.</p>
            <p class="text-on-surface-variant">Please note that all re-delivery or rebooking fees will be shouldered by the customer, as rider time and delivery costs have already been incurred.</p>
          </div>

          <div>
            <h3 class="font-headline-sm text-headline-sm text-on-surface mb-2">4. Valid Refund or Replacement Cases</h3>
            <p class="text-on-surface-variant mb-2">Refunds or replacements may be considered only in the following circumstances:</p>
            <ul class="list-disc list-inside text-on-surface-variant space-y-2 ml-4 mb-2">
              <li>Wrong item received</li>
              <li>Missing item</li>
              <li>Item arrived damaged or spoiled upon delivery</li>
            </ul>
            <p class="text-on-surface-variant mb-2">Requirements:</p>
            <ul class="list-disc list-inside text-on-surface-variant space-y-2 ml-4 mb-2">
              <li>Report must be made within 24 hours of delivery</li>
              <li><strong>No Video Upon Receiving, No Return, No Refund</strong></li>
              <li>Clear photos and/or videos must be provided</li>
              <li>Packaging must remain unopened and intact</li>
            </ul>
            <p class="text-on-surface-variant">Approved cases may be resolved through replacement, store credit, or refund &mdash; subject to evaluation.</p>
          </div>

          <div>
            <h3 class="font-headline-sm text-headline-sm text-on-surface mb-2">5. Refund Processing Time</h3>
            <p class="text-on-surface-variant mb-2">In cases where a refund is approved and confirmed by management, processing times may vary depending on the payment method used.</p>
            <p class="text-on-surface-variant mb-2">For debit and credit card payments processed through Maya, refunds may take up to 30 business days or longer to reflect on the customer&rsquo;s account after the refund has been confirmed, subject to the policies and processing timelines of Maya and the customer&rsquo;s issuing bank.</p>
            <p class="text-on-surface-variant">By placing an order, the customer acknowledges and agrees to these refund processing timelines.</p>
          </div>

          <div>
            <h3 class="font-headline-sm text-headline-sm text-on-surface mb-2">6. Return of Frozen Products</h3>
            <p class="text-on-surface-variant mb-2">Due to food safety and hygiene considerations, returns of frozen products are generally not accepted.</p>
            <p class="text-on-surface-variant mb-2">However, in exceptional cases and subject to evaluation, management reserves the right to approve or deny any request for return, replacement, or refund.</p>
            <p class="text-on-surface-variant">When applicable, resolutions may be processed without requiring the physical return of the item.</p>
          </div>

          <div>
            <h3 class="font-headline-sm text-headline-sm text-on-surface mb-2">7. Inspection Upon Receipt</h3>
            <p class="text-on-surface-variant mb-2">Customers are encouraged to inspect the items immediately before opening the packaging.</p>
            <p class="text-on-surface-variant">Acceptance of the delivery confirms that the products were received in good condition.</p>
          </div>

          <div>
            <h3 class="font-headline-sm text-headline-sm text-on-surface mb-2">8. Policy Enforcement</h3>
            <p class="text-on-surface-variant mb-2">All refund, replacement, and re-delivery requests are evaluated in accordance with this policy.</p>
            <p class="text-on-surface-variant">Management&rsquo;s decision shall be final.</p>
          </div>
        </div>
      </section>

      <section>
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-3">3. Business Registration</h2>
        <p class="text-on-surface-variant">The Daily Cuts is a registered business operating in the Philippines. Registration details available upon request.</p>
        <ul class="list-disc list-inside text-on-surface-variant space-y-2 ml-4">
          <li><strong>Business Name:</strong> The Daily Cuts</li>
          <li><strong>Address:</strong> {{ $storeLocation?->fullAddress() ?? 'Phase 10, Wellington Place' }}</li>
          <li><strong>Email:</strong> <a href="mailto:tdc@thedailycuts.com" class="text-primary hover:underline">tdc@thedailycuts.com</a></li>
          <li><strong>Phone:</strong> <a href="tel:+631234567891" class="text-primary hover:underline">+63 1234567891</a></li>
          <li><strong>DTI/SEC Registration Number:</strong> <em>To be added</em></li>
          <li><strong>BIR TIN:</strong> <em>To be added</em></li>
        </ul>
        <p class="text-on-surface-variant mt-3">Note: The Registration Number and BIR TIN fields are placeholders. Provide the actual details and we can insert them, or we can remove them if you prefer not to display them.</p>
      </section>

      <section>
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-3">4. Questions</h2>
        <p class="text-on-surface-variant">For any questions about our policies, please contact us through the details above or visit our <a href="{{ route('faq') }}" class="text-primary hover:underline font-bold">FAQ</a> page.</p>
      </section>

    </div>
  </div>
</main>

@endsection
