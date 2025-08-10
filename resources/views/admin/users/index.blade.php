<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin • Users</title>
  <link rel="preconnect" href="https://cdn.jsdelivr.net">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-info">
  <div class="container">
    <a class="navbar-brand" href="{{ route('admin.dashboard') }}">Wallet • Admin</a>
    <div class="ml-auto">
      <a class="btn btn-outline-light btn-sm" href="{{ route('admin.users.create') }}">Create User</a>
    </div>
  </div>
</nav>
<main class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Users</h4>
    <form class="form-inline" method="GET" action="{{ route('admin.users.index') }}">
      <input type="text" name="search" class="form-control form-control-sm mr-2" placeholder="Search name/email" value="{{ request('search') }}">
      <button class="btn btn-sm btn-outline-secondary" type="submit">Search</button>
    </form>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead class="thead-light">
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Amount</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($users as $user)
            <tr>
              <td>{{ $user->id }}</td>
              <td>{{ $user->name }}</td>
              <td>{{ $user->email }}</td>
              <td><span class="badge badge-pill badge-{{ $user->role === 'admin' ? 'dark' : 'info' }}">{{ $user->role }}</span></td>
              <td>{{ number_format((float)($user->amount ?? 0), 2) }}</td>
              <td class="text-right">
                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.users.show', $user) }}">View</a>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.users.edit', $user) }}">Edit</a>
                <form class="d-inline" method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user?');">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted">No users found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if(method_exists($users, 'links'))
    <div class="card-footer">{{ $users->links() }}</div>
    @endif
  </div>
</main>
</body>
</html>
