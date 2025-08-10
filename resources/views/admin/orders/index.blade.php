<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin • Orders</title>
  <link rel="preconnect" href="https://cdn.jsdelivr.net">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
  <style>
    .badge-status{font-size:.85rem}
  </style>
</head>
<body class="bg-light">
  <nav class="navbar navbar-expand-lg navbar-dark bg-info">
    <div class="container">
      <a class="navbar-brand font-weight-bold" href="#">Wallet • Admin</a>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav mr-auto">
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.dashboard') }}">Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('admin.users.index') }}">Users</a></li>
          <li class="nav-item active"><a class="nav-link" href="{{ route('admin.orders.index') }}">Orders</a></li>
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
      <h4 class="mb-2 mb-md-0">Orders</h4>
      <form method="GET" action="{{ route('admin.orders.index') }}" class="form-inline">
        <input name="search" class="form-control form-control-sm mr-2" type="search" value="{{ request('search') }}" placeholder="Search orders...">
        <button class="btn btn-sm btn-info" type="submit">Search</button>
      </form>
    </div>

    <div class="row">
      <div class="col-md-3 mb-3">
        <div class="card shadow-sm"><div class="card-body">
          <div class="text-muted small">Total</div>
          <div class="h5 mb-0">{{ $stats['total_orders'] ?? 0 }}</div>
        </div></div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card shadow-sm"><div class="card-body">
          <div class="text-muted small">Pending</div>
          <div class="h5 mb-0">{{ $stats['pending_orders'] ?? 0 }}</div>
        </div></div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card shadow-sm"><div class="card-body">
          <div class="text-muted small">Completed</div>
          <div class="h5 mb-0">{{ $stats['completed_orders'] ?? 0 }}</div>
        </div></div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card shadow-sm"><div class="card-body">
          <div class="text-muted small">Total Amount</div>
          <div class="h5 mb-0">${{ number_format((float)($stats['total_amount'] ?? 0), 2) }}</div>
        </div></div>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
          <thead class="thead-light">
            <tr>
              <th>ID</th>
              <th>User</th>
              <th>Title</th>
              <th class="text-right">Amount</th>
              <th>Status</th>
              <th>Created</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
          @forelse($orders as $order)
            <tr>
              <td>#{{ $order->id }}</td>
              <td>{{ optional($order->user)->name }}<br><small class="text-muted">{{ optional($order->user)->email }}</small></td>
              <td>{{ $order->title }}</td>
              <td class="text-right">${{ number_format((float)$order->amount, 2) }}</td>
              <td>
                @php($status = $order->status)
                <span class="badge badge-status {{ $status === 'completed' ? 'badge-success' : ($status === 'pending_payment' ? 'badge-warning' : ($status === 'cancelled' ? 'badge-secondary' : 'badge-info')) }}">
                  {{ ucfirst(str_replace('_',' ', $status)) }}
                </span>
              </td>
              <td>{{ $order->created_at?->format('Y-m-d H:i') }}</td>
              <td class="text-right"><a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">View</a></td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted p-4">No orders found.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
      @if(method_exists($orders, 'links'))
      <div class="card-footer">{{ $orders->links() }}</div>
      @endif
    </div>

    <div class="text-center mt-4">
      <a href="{{ route('admin.dashboard') }}" class="btn btn-link">← Back to Dashboard</a>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous"></script>
</body>
</html>
