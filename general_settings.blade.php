@extends('Huzaifa_dashboard.app')  <!-- Extend your master layout -->

@section('content')
<div class="container mt-4">
    <h2 class="mb-4">Admin Settings</h2>

    <div class="row">
        <div class="col-md-4 text-center">
            <!-- Admin Profile Image -->
            <div class="profile-img">
                <img src="{{ asset($admin->image) }}" alt="Admin Profile" class="rounded-circle" width="150" height="150">
            </div>
            <div class="mt-3">
                <button class="btn btn-primary" data-toggle="modal" data-target="#updateProfileImageModal">Update Profile Image</button>
            </div>
        </div>
    
        <div class="col-md-8">
            <!-- Admin Info -->
            <form>
                <div class="form-group">
                    <label for="adminName">Name</label>
                    <input type="text" class="form-control" id="adminName" value="{{ $admin->name }}" readonly>
                </div>
                <div class="form-group">
                    <label for="adminEmail">Email</label>
                    <input type="email" class="form-control" id="adminEmail" value="{{ $admin->email }}" readonly>
                </div>
                
                <div class="mt-3">
                    <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#updateDetailsModal">Edit Details</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Profile Image Modal -->
<div class="modal fade" id="updateProfileImageModal" tabindex="-1" role="dialog" aria-labelledby="updateProfileImageModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="updateProfileImageModalLabel">Update Profile Image</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form action="{{ route('admin.update.image') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label for="image">Select New Image</label>
                <input type="file" class="form-control" name="image" id="image" accept="image/*" required>
            </div>
            <button type="submit" class="btn btn-primary">Upload Image</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Edit Details Modal -->
<div class="modal fade" id="updateDetailsModal" tabindex="-1" role="dialog" aria-labelledby="updateDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="updateDetailsModalLabel">Edit Admin Details</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form action="{{ route('admin.update.details') }}" method="POST">
              @csrf
              <div class="form-group">
                  <label for="adminName">Name</label>
                  <input type="text" class="form-control" name="name" id="adminName" value="{{ $admin->name }}" required>
              </div>
              <div class="form-group">
                  <label for="adminEmail">Email</label>
                  <input type="email" class="form-control" name="email" id="adminEmail" value="{{ $admin->email }}" required>
              </div>
              <div class="form-group">
                  <label for="oldPassword">Old Password</label>
                  <input type="password" class="form-control" name="old_password" id="oldPassword" placeholder="Enter Old Password" required>
              </div>
              <div class="form-group">
                  <label for="newPassword">New Password (optional)</label>
                  <input type="password" class="form-control" name="password" id="newPassword" placeholder="Enter New Password">
              </div>
              <div class="form-group">
                  <label for="confirmPassword">Confirm New Password</label>
                  <input type="password" class="form-control" name="password_confirmation" id="confirmPassword" placeholder="Confirm New Password">
              </div>
              <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                  <button type="submit" class="btn btn-primary">Save Changes</button> 
              </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  

@if ($errors->any())
    <div class="alert alert-danger mt-3">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if (session('success'))
    <div class="alert alert-success mt-3">
        {{ session('success') }}
    </div>
@endif

<!-- Custom styling -->
<style>
    .profile-img img {
        border: 3px solid #ddd;
        padding: 5px;
        background-color: white;
    }

    .btn-warning {
        background-color: #ffc107;
        border-color: #ffc107;
        color: white;
    }

    .btn-warning:hover {
        background-color: #e0a800;
    }
</style>
@endsection
