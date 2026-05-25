<div class="modal-header">
    <h5 class="modal-title">
        <i class="fa fa-list-alt"></i>
        Monitoring LKH — {{ $pegawai->name }}
        <small class="text-muted">({{ $labelBulan }} {{ $tahun }})</small>
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <div class="mb-10">
        <strong>NIP:</strong> {{ $pegawai->nip ?? '—' }}
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-sm">
            <thead>
            <tr>
                <th style="width: 120px;">Tanggal</th>
                <th>Kegiatan</th>
                <th>Output</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ \Illuminate\Support\Carbon::parse($row->tanggal)->locale('id')->translatedFormat('d M Y') }}</td>
                    <td>{!! nl2br(e($row->kegiatan)) !!}</td>
                    <td>{{ $row->output }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center text-muted">Tidak ada data LKH pada periode ini.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
</div>
