<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Order #{{ $order->id }}</title>
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
          <li class="nav-item active"><a class="nav-link" href="{{ route('customer.orders.index') }}">Orders</a></li>
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
      <a href="{{ route('customer.orders.index') }}" class="btn btn-sm btn-outline-secondary">Back to Orders</a>
    </div>

    @if (session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
      <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
      <div class="col-lg-8 mb-3">
        <div class="card shadow-sm">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h5 class="mb-0">Details</h5>
              @php($status = $order->status)
              <span class="badge {{ $status === 'completed' ? 'badge-success' : ($status === 'pending_payment' ? 'badge-warning' : ($status === 'cancelled' ? 'badge-secondary' : ($status === 'refunded' ? 'badge-info' : 'badge-light'))) }}">
                {{ ucfirst(str_replace('_',' ', $status)) }}
              </span>
            </div>
            <dl class="row mb-0">
              <dt class="col-sm-4">Title</dt>
              <dd class="col-sm-8">{{ $order->title }}</dd>
              <dt class="col-sm-4">Amount</dt>
              <dd class="col-sm-8">${{ number_format((float)$order->amount, 2) }}</dd>
              <dt class="col-sm-4">Created</dt>
              <dd class="col-sm-8">{{ $order->created_at?->format('Y-m-d H:i') }}</dd>
              <dt class="col-sm-4">Updated</dt>
              <dd class="col-sm-8">{{ $order->updated_at?->format('Y-m-d H:i') }}</dd>
              @if($order->description)
              <dt class="col-sm-4">Description</dt>
              <dd class="col-sm-8">{{ $order->description }}</dd>
              @endif
            </dl>
          </div>
        </div>

        <div class="card shadow-sm mt-3">
          <div class="card-header bg-white"><strong>Related Transactions</strong></div>
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
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
              @forelse($order->transactions as $tx)
                <tr>
                  <td>#{{ $tx->id }}</td>
                  <td>
                    <span class="badge {{ $tx->type === 'credit' ? 'badge-success' : 'badge-danger' }}">{{ ucfirst($tx->type) }}</span>
                  </td>
                  <td class="text-right">${{ number_format((float)$tx->amount, 2) }}</td>
                  <td>{{ $tx->getFormattedDescription() }}</td>
                  <td>{{ $tx->created_at?->format('Y-m-d H:i') }}</td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center text-muted p-4">No transactions for this order.</td></tr>
              @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="col-lg-4 mb-3">
        <div class="card shadow-sm">
          <div class="card-body">
            <h6 class="text-muted">Status</h6>
            @if($order->status === 'pending_payment')
              <div class="alert alert-info mb-2">
                Order received and pending payment. An admin will process and complete this order.
              </div>
            @else
              <div class="text-muted small">No actions available for this status.</div>
            @endif
          </div>
        </div>
        <div class="card shadow-sm mt-3">
          <div class="card-body">
            <a href="{{ route('customer.orders.index') }}" class="btn btn-link btn-block">← Back to Orders</a>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous"></script>
</body>
</html>
