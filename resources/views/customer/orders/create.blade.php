<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Add Money</title>
  <link rel="preconnect" href="https://cdn.jsdelivr.net">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
</head>
<body class="bg-light">
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
      <a class="navbar-brand font-weight-bold" href="#">Wallet • Merchant</a>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav mr-auto">
          <li class="nav-item"><a class="nav-link" href="{{ route('merchant.dashboard') }}">Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('customer.transactions.index') }}">Transactions</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('customer.orders.index') }}">Orders</a></li>
        </ul>
        <form method="POST" action="{{ route('logout') }}" class="form-inline my-2 my-lg-0">
          @csrf
          <button type="submit" class="btn btn-outline-light btn-sm">Logout</button>
        </form>
      </div>
    </div>
  </nav>

  <main class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="mb-0">Add Money</h4>
      <a href="{{ route('customer.orders.index') }}" class="btn btn-sm btn-outline-secondary">Back to Orders</a>
    </div>

    @if ($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @if (session('error'))
      <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm">
      <div class="card-body">
        <form method="POST" action="{{ route('customer.orders.store') }}">
          @csrf
          <div class="form-row">
            <div class="form-group col-md-4">
              <label for="amount">Amount</label>
              <input type="number" step="0.01" min="0" class="form-control" name="amount" id="amount" value="{{ old('amount') }}" required>
            </div>
            <div class="form-group col-md-8">
              <label for="title">Title</label>
              <input type="text" class="form-control" name="title" id="title" value="{{ old('title', 'Add Money to Wallet') }}">
            </div>
          </div>
          <div class="form-group">
            <label for="description">Description</label>
            <textarea class="form-control" name="description" id="description" rows="3">{{ old('description') }}</textarea>
          </div>
          <div class="form-group">
            <label for="payment_method">Payment Method</label>
            <select class="form-control" name="payment_method" id="payment_method">
              <option value="manual" {{ old('payment_method') === 'manual' ? 'selected' : '' }}>Manual</option>
              <option value="card" {{ old('payment_method') === 'card' ? 'selected' : '' }}>Card (demo)</option>
              <option value="bank" {{ old('payment_method') === 'bank' ? 'selected' : '' }}>Bank (demo)</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary">Create Order</button>
        </form>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous"></script>
</body>
</html>
