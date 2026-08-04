@extends('backend.main.index')
@push('title', 'Dokumentasi API Integrasi')

@section('content')
    <div class="content-wrapper">
        <div class="container-full">
            <section class="content">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0">Dokumentasi API Integrasi</h4>
                                <a href="/docs/api" target="_blank" class="btn btn-sm btn-outline-primary">Buka Layar Penuh <i class="fa fa-external-link"></i></a>
                            </div>
                            <div class="card-body p-0">
                                <iframe src="/docs/api" width="100%" height="800px" style="border:none; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
