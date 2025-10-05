<form method="POST" action="/verify-otp">
    @csrf
    <input type="hidden" name="phone" value="{{ old('phone') }}">
    <label>Enter OTP sent to your phone:</label>
    <input type="text" name="code" required>
    <button type="submit">Verify</button>
</form>
