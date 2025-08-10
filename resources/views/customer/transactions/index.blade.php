<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Transactions</title>
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
          <li class="nav-item active"><a class="nav-link" href="{{ route('customer.transactions.index') }}">Transactions</a></li>
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
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-3">
      <h4 class="mb-2 mb-md-0">My Transactions</h4>
      <form method="GET" action="{{ route('customer.transactions.index') }}" class="form-inline">
        <input name="search" class="form-control form-control-sm mr-2" type="search" value="{{ request('search') }}" placeholder="Search description...">
        <button class="btn btn-sm btn-primary" type="submit">Search</button>
      </form>
    </div>

    @if (session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
      <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
      <div class="col-md-3 mb-3">
        <div class="card shadow-sm"><div class="card-body">
          <div class="text-muted small">Current Balance</div>
          <div class="h5 mb-0">${{ number_format((float)($stats['current_balance'] ?? 0), 2) }}</div>
        </div></div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card shadow-sm"><div class="card-body">
          <div class="text-muted small">Total Credits</div>
          <div class="h5 mb-0">${{ number_format((float)($stats['total_credits'] ?? 0), 2) }}</div>
        </div></div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card shadow-sm"><div class="card-body">
          <div class="text-muted small">Total Debits</div>
          <div class="h5 mb-0">${{ number_format((float)($stats['total_debits'] ?? 0), 2) }}</div>
        </div></div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card shadow-sm"><div class="card-body">
          <div class="text-muted small">Transactions</div>
          <div class="h5 mb-0">{{ $stats['transaction_count'] ?? 0 }}</div>
        </div></div>
      </div>
    </div>

    <div class="d-flex justify-content-end mb-2">
      <a href="{{ route('customer.transactions.transfer-form') }}" class="btn btn-primary mr-2">Transfer Money</a>
      <a href="{{ route('customer.orders.create') }}" class="btn btn-outline-primary">Add Money</a>
    </div>

    <div class="card shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
          <thead class="thead-light">
            <tr>
              <th>ID</th>
              <th>Type</th>
              <th class="text-right">Amount</th>
              <th>Description</th>
              <th>Created</th>
            </tr>
          </thead>
          <tbody>
          @forelse($transactions as $tx)
            <tr>
              <td>#{{ $tx->id }}</td>
              <td><span class="badge {{ $tx->type === 'credit' ? 'badge-success' : 'badge-danger' }}">{{ ucfirst($tx->type) }}</span></td>
              <td class="text-right">${{ number_format((float)$tx->amount, 2) }}</td>
              <td>{{ $tx->getFormattedDescription() }}</td>
              <td>{{ $tx->created_at?->format('Y-m-d H:i') }}</td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center text-muted p-4">No transactions yet.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
      @if(method_exists($transactions, 'links'))
      <div class="card-footer">{{ $transactions->links() }}</div>
      @endif
    </div>

    <div class="text-center mt-4">
      <a href="{{ route('merchant.dashboard') }}" class="btn btn-link">← Back to Dashboard</a>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous"></script>
</body>
</html>
