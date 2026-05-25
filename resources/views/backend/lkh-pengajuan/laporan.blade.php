<div class="modal-header">
    <h5 class="modal-title">
        <i class="fa fa-list-alt"></i>
        Laporan LKH — {{ $pengajuan->user?->name ?? 'Pegawai' }}
        @php
            $labelBulan = (string) $pengajuan->bulan;
            try {
                $m = str_pad((string) $pengajuan->bulan, 2, '0', STR_PAD_LEFT);
                $labelBulan = \Illuminate\Support\Carbon::createFromFormat('m', $m)->locale('id')->translatedFormat('F');
            } catch (\Throwable $e) {
                // keep numeric bulan
            }
        @endphp
        <small class="text-muted">({{ $labelBulan }} {{ $pengajuan->tahun }})</small>
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <div class="mb-15">
        @if($pengajuan->status === \App\Models\LkhPengajuan::STATUS_PENDING)
            <span class="badge badge-warning">Menunggu persetujuan</span>
        @elseif($pengajuan->status === \App\Models\LkhPengajuan::STATUS_APPROVED)
            <span class="badge badge-success">Disetujui</span>
        @else
            <span class="badge badge-danger">Revisi</span>
        @endif
        @if($pengajuan->catatan_atasan)
            <div class="alert alert-warning mt-10 mb-0">
                <strong>Catatan atasan:</strong><br>{{ $pengajuan->catatan_atasan }}
            </div>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-sm">
            <thead>
            <tr>
                <th style="width:120px;">Tanggal</th>
                <th>Kegiatan</th>
                <th>Output</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->tanggal }}</td>
                    <td>{!! nl2br(e($row->kegiatan)) !!}</td>
                    <td>{{ $row->output }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center text-muted">Tidak ada baris LKH untuk periode ini.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
</div>
