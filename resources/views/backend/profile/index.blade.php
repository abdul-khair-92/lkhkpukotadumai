@extends('backend.main.index')
@push('title', 'Profil Saya')

@section('content')
    <div class="content-wrapper">
        <div class="container-full">
            <section class="content">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Profil Saya</h4>
                            </div>
                            <div class="card-body">
                                @if (session('success'))
                                    <div class="alert alert-success">
                                        {{ session('success') }}
                                    </div>
                                @endif
                                @if (session('error'))
                                    <div class="alert alert-danger">
                                        {{ session('error') }}
                                    </div>
                                @endif

                                <div class="row">
                                    <div class="col-md-4 text-center">
                                        @if(auth()->user()->photo)
                                            <img src="{{ asset('storage/' . auth()->user()->photo) }}" alt="Foto Profil" class="img-fluid rounded-circle mb-3" style="width: 200px; height: 200px; object-fit: cover;" id="profile-picture-preview">
                                        @else
                                            <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=random" alt="Foto Profil Default" class="img-fluid rounded-circle mb-3" style="width: 200px; height: 200px; object-fit: cover;" id="profile-picture-preview">
                                        @endif

                                        <form action="{{ route('profile.photo.update') }}" method="POST" enctype="multipart/form-data" id="form-upload-photo">
                                            @csrf
                                            <div class="form-group mb-3 text-start">
                                                <label for="photo" class="form-label">Ubah Foto Profil</label>
                                                <div class="input-group">
                                                    <input type="file" name="photo" id="photo" class="form-control @error('photo') is-invalid @enderror" accept="image/jpeg,image/png,image/jpg" required>
                                                    <button type="submit" class="btn btn-primary" id="btn-upload-photo">Upload</button>
                                                </div>
                                                <div class="invalid-feedback d-block" id="photo-error-message"></div>
                                                @error('photo')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </form>
                                    </div>
                                    <div class="col-md-8 mt-4 mt-md-0">
                                        <h5 class="mb-3">Informasi Pribadi</h5>
                                        <table class="table table-borderless table-striped">
                                            <tr>
                                                <th width="200">Nama Lengkap</th>
                                                <td>{{ auth()->user()->name }}</td>
                                            </tr>
                                            <tr>
                                                <th>NIP</th>
                                                <td>{{ auth()->user()->nip ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Email</th>
                                                <td>{{ auth()->user()->email }}</td>
                                            </tr>
                                        </table>
                                        <div class="text-end mt-3">
                                            <a class="btn btn-primary btn-action" href="#" data-action="edit"
                                               data-url="{{ url(config('master.app.url.backend').'/user') }}" data-title="Edit Data User"
                                               data-id="{{ auth()->user()->id }}">
                                                <i class="ti-pencil-alt me-2"></i>Edit Profil Saya
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
@push('js')
    <script src="{{ url($template.'/assets/vendor_components/jquery-validation-1.17.0/lib/jquery.form.js') }}"></script>
    <script src="{{ url('js/jquery-crud.js?id='.time()) }}"></script>
    <script>
        $(document).ready(function() {
            $('#form-upload-photo').on('submit', function(e) {
                e.preventDefault();
                let form = $(this);
                let formData = new FormData(this);
                let btn = $('#btn-upload-photo');
                let errorContainer = $('#photo-error-message');
                
                errorContainer.text('');

                $.ajax({
                    url: form.attr('action'),
                    type: form.attr('method'),
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function() {
                        btn.prop('disabled', true).text('Uploading...');
                    },
                    success: function(response) {
                        btn.prop('disabled', false).text('Upload');
                        if (response.status === 'success') {
                            swal('Berhasil', response.message, 'success');
                            
                            // Update image preview in profile page
                            $('#profile-picture-preview').attr('src', response.photo_url);
                            
                            // Update header profile picture
                            let headerImg = $('.user-menu .dropdown-toggle img');
                            if(headerImg.length) {
                                headerImg.attr('src', response.photo_url);
                            } else {
                                $('.user-menu .dropdown-toggle i.icon-User').replaceWith('<img src="'+response.photo_url+'" alt="User" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover; margin-top: -5px;">');
                            }
                            
                            // Clear file input
                            $('#photo').val('');
                        }
                    },
                    error: function(xhr) {
                        btn.prop('disabled', false).text('Upload');
                        let errorMessage = 'Terjadi kesalahan saat mengunggah foto.';
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            if (errors && errors.photo) {
                                errorMessage = errors.photo[0];
                                errorContainer.text(errorMessage);
                            }
                        }
                        swal('Gagal', errorMessage, 'error');
                    }
                });
            });
        });
    </script>
@endpush
