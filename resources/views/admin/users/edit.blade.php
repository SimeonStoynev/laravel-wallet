<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit User</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-info">
  <div class="container">
    <a class="navbar-brand" href="{{ route('admin.users.show', $user) }}">← Back</a>
  </div>
</nav>
<main class="container my-4">
  <h4>Edit User #{{ $user->id }}</h4>
  @if($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif
  <form method="POST" action="{{ route('admin.users.update', $user) }}">
    @csrf
    @method('PUT')
    <div class="form-group">
      <label>Name</label>
      <input class="form-control" name="name" value="{{ old('name', $user->name) }}" required>
    </div>
    <div class="form-group">
      <label>Email</label>
      <input type="email" class="form-control" name="email" value="{{ old('email', $user->email) }}" required>
    </div>
    <div class="form-group">
      <label>Password (leave blank to keep)</label>
      <input type="password" class="form-control" name="password">
    </div>
    <div class="form-group">
      <label>Role</label>
      <select class="form-control" name="role">
        <option value="merchant" {{ old('role', $user->role)==='merchant' ? 'selected' : '' }}>merchant</option>
        <option value="admin" {{ old('role', $user->role)==='admin' ? 'selected' : '' }}>admin</option>
      </select>
    </div>
    <button class="btn btn-primary" type="submit">Save</button>
    <a class="btn btn-link" href="{{ route('admin.users.show', $user) }}">Cancel</a>
  </form>
</main>
</body>
</html>
