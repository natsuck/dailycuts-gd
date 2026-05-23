<h2>New Reseller Inquiry</h2>

<p><strong>Name:</strong> {{ $inquiry['name'] }}</p>
<p><strong>Email:</strong> {{ $inquiry['email'] }}</p>
<p><strong>Phone:</strong> {{ $inquiry['phone'] ?: 'Not provided' }}</p>

<p><strong>Message:</strong></p>
<p>{{ $inquiry['message'] }}</p>
