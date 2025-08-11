<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin • Order #{{ $order->id }}</title>
  <link rel="preconnect" href="https://cdn.jsdelivr.net">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
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
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="mb-0">Order #{{ $order->id }}</h4>
      <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-secondary">Back to Orders</a>
    </div>

    <div class="row">
      <div class="col-md-8 mb-3">
        <div class="card shadow-sm">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0">{{ $order->title }}</h5>
              @php($status = $order->status)
              <span class="badge {{ $status === 'completed' ? 'badge-success' : ($status === 'pending_payment' ? 'badge-warning' : ($status === 'cancelled' ? 'badge-secondary' : 'badge-info')) }}">
                {{ ucfirst(str_replace('_',' ', $status)) }}
              </span>
            </div>
            <hr>
            <dl class="row mb-0">
              <dt class="col-sm-3">User</dt>
              <dd class="col-sm-9">{{ optional($order->user)->name }} <small class="text-muted">{{ optional($order->user)->email }}</small></dd>

              <dt class="col-sm-3">Amount</dt>
              <dd class="col-sm-9">${{ number_format((float)$order->amount, 2) }}</dd>

              <dt class="col-sm-3">Description</dt>
              <dd class="col-sm-9">{{ $order->description ?? '—' }}</dd>

              <dt class="col-sm-3">External Ref</dt>
              <dd class="col-sm-9">{{ $order->external_reference ?? '—' }}</dd>

              <dt class="col-sm-3">Created</dt>
              <dd class="col-sm-9">{{ $order->created_at?->format('Y-m-d H:i') }}</dd>

              <dt class="col-sm-3">Metadata</dt>
              <dd class="col-sm-9"><pre class="mb-0 bg-light p-2 border rounded" style="white-space:pre-wrap;">{{ json_encode($order->metadata ?? [], JSON_PRETTY_PRINT) }}</pre></dd>
            </dl>
          </div>
        </div>

        <div class="card shadow-sm mt-3">
          <div class="card-body">
            <h6 class="mb-3">Actions</h6>
            @if($order->status === \App\Models\Order::STATUS_PENDING_PAYMENT)
              <form method="POST" action="{{ route('admin.orders.process', $order) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success btn-sm">Process</button>
              </form>
              <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" class="d-inline ml-2">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm">Cancel</button>
              </form>
            @elseif($order->status === \App\Models\Order::STATUS_COMPLETED)
              <form method="POST" action="{{ route('admin.orders.refund', $order) }}" class="form-inline">
                @csrf
                <div class="form-group mr-2 mb-2">
                  <label for="amount" class="sr-only">Amount</label>
                  <input type="number" step="0.01" min="0" max="{{ $order->amount }}" class="form-control form-control-sm" name="amount" id="amount" placeholder="Refund amount (optional)">
                </div>
                <button type="submit" class="btn btn-warning btn-sm mb-2">Refund</button>
              </form>
            @elseif(in_array($order->status, [\App\Models\Order::STATUS_REFUNDED, \App\Models\Order::STATUS_CANCELLED], true))
              <div class="text-muted small">No actions available.</div>
            @endif
            @if (session('success'))
              <div class="alert alert-success mt-3 mb-0">{{ session('success') }}</div>
            @endif
            @if (session('error'))
              <div class="alert alert-danger mt-3 mb-0">{{ session('error') }}</div>
            @endif
          </div>
        </div>
      </div>

      <div class="col-md-4 mb-3">
        <div class="card shadow-sm">
          <div class="card-body">
            <h6 class="mb-3">Related Transactions</h6>
            @forelse($order->transactions as $txn)
              <div class="border rounded p-2 mb-2">
                <div class="d-flex justify-content-between">
                  <strong>{{ ucfirst($txn->type) }}</strong>
                  <span>${{ number_format((float)$txn->amount, 2) }}</span>
                </div>
                <div class="text-muted small">{{ $txn->created_at?->format('Y-m-d H:i') }}</div>
                <div class="small">{{ $txn->description }}</div>
              </div>
            @empty
              <div class="text-muted">No transactions.</div>
            @endforelse
          </div>
        </div>
      </div>
    </div>

    <div class="text-center mt-4">
      <a href="{{ route('admin.orders.index') }}" class="btn btn-link">← Back to Orders</a>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous"></script>
</body>
</html>
