<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>User Details</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-info">
  <div class="container">
    <a class="navbar-brand" href="{{ route('admin.users.index') }}">← Users</a>
    <div class="ml-auto">
      <a class="btn btn-outline-light btn-sm" href="{{ route('admin.users.edit', $user) }}">Edit</a>
      <form class="d-inline" method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user?');">
        @csrf
        @method('DELETE')
        <button class="btn btn-outline-light btn-sm" type="submit">Delete</button>
      </form>
    </div>
  </div>
</nav>
<main class="container my-4">
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  <div class="row">
    <div class="col-md-5 mb-3">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <h5 class="card-title mb-3">User #{{ $user->id }}</h5>
          <dl class="row mb-0">
            <dt class="col-5">Name</dt><dd class="col-7">{{ $user->name }}</dd>
            <dt class="col-5">Email</dt><dd class="col-7">{{ $user->email }}</dd>
            <dt class="col-5">Role</dt><dd class="col-7"><span class="badge badge-{{ $user->role==='admin' ? 'dark':'info' }}">{{ $user->role }}</span></dd>
            <dt class="col-5">Balance</dt><dd class="col-7">{{ number_format((float)($user->amount ?? 0), 2) }}</dd>
          </dl>
        </div>
      </div>
    </div>

    <div class="col-md-7 mb-3">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Add Money</h5>
          <form method="POST" action="{{ route('admin.users.add-money', $user) }}" class="mb-3">
            @csrf
            <div class="form-row">
              <div class="form-group col-sm-4">
                <label>Amount</label>
                <input type="number" step="0.01" min="0.01" class="form-control" name="amount" required>
              </div>
              <div class="form-group col-sm-8">
                <label>Description <span class="text-muted small">(optional)</span></label>
                <input class="form-control" name="description" placeholder="Optional">
              </div>
            </div>
            <button class="btn btn-primary" type="submit">Add Credit</button>
          </form>

          <hr>
          <h5 class="card-title mt-3">Debit Money</h5>
          <form method="POST" action="{{ route('admin.users.remove-money', $user) }}">
            @csrf
            <div class="form-row">
              <div class="form-group col-sm-4">
                <label>Amount</label>
                <input type="number" step="0.01" min="0.01" class="form-control" name="amount" required>
              </div>
              <div class="form-group col-sm-8">
                <label>Description <span class="text-muted small">(optional)</span></label>
                <input class="form-control" name="description" placeholder="Optional">
              </div>
            </div>
            <button class="btn btn-outline-danger" type="submit">Create Debit</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm mt-3">
    <div class="card-body">
      <h5 class="card-title">Transactions</h5>
      <div class="table-responsive">
        <table class="table table-sm table-striped">
          <thead class="thead-light">
            <tr>
              <th>ID</th>
              <th>Type</th>
              <th>Amount</th>
              <th>Description</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
          @forelse(($transactions ?? []) as $t)
            <tr>
              <td>{{ $t->id }}</td>
              <td>{{ $t->type ?? '' }}</td>
              <td>{{ number_format((float)($t->amount ?? 0), 2) }}</td>
              <td>{{ $t->description ?? '' }}</td>
              <td>{{ $t->created_at ?? '' }}</td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-muted text-center">No transactions.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>
</body>
</html>
