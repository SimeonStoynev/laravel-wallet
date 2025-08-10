<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Merchant Dashboard</title>
  <link rel="preconnect" href="https://cdn.jsdelivr.net">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
  <style>
    .hero {
      background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
      color: #fff;
      border-radius: .5rem;
    }
    .hero .lead { opacity: .9; }
  </style>
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
          <li class="nav-item active"><a class="nav-link" href="{{ route('merchant.dashboard') }}">Dashboard</a></li>
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
    <div class="hero p-4 mb-4 shadow-sm">
      <h4 class="mb-1">Welcome, {{ auth()->user()->name }}</h4>
      <p class="lead mb-0">Manage your balance, transfers, and orders in one place.</p>
    </div>

    <div class="row">
      <div class="col-md-6 col-lg-3 mb-3">
        <div class="card h-100 shadow-sm">
          <div class="card-body d-flex flex-column">
            <h6 class="text-muted">Balance</h6>
            <p class="display-4 mb-3">—</p>
            <a href="{{ route('customer.balance') }}" class="btn btn-primary mt-auto">View Balance</a>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-3 mb-3">
        <div class="card h-100 shadow-sm">
          <div class="card-body d-flex flex-column">
            <h6 class="text-muted">Transfers</h6>
            <p class="mb-3">Send money to another wallet.</p>
            <a href="{{ route('customer.transactions.transfer-form') }}" class="btn btn-outline-primary mt-auto">Make a Transfer</a>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-3 mb-3">
        <div class="card h-100 shadow-sm">
          <div class="card-body d-flex flex-column">
            <h6 class="text-muted">Transactions</h6>
            <p class="mb-3">View your recent activity.</p>
            <a href="{{ route('customer.transactions.index') }}" class="btn btn-outline-primary mt-auto">View Transactions</a>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-3 mb-3">
        <div class="card h-100 shadow-sm">
          <div class="card-body d-flex flex-column">
            <h6 class="text-muted">Orders</h6>
            <p class="mb-3">Add money and manage orders.</p>
            <a href="{{ route('customer.orders.index') }}" class="btn btn-outline-primary mt-auto">Go to Orders</a>
          </div>
        </div>
      </div>
    </div>

    <div class="text-center mt-4">
      <a href="/" class="btn btn-link">← Back to React App</a>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous"></script>
</body>
</html>
