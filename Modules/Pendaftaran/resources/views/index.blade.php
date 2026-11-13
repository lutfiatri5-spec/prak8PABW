@extends('layouts.app')
@section('title', 'Pendaftaran')
@section('content')
<div id="pendaftaran-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1">Pendaftaran</h4>
            <p class="text-muted mb-0 small">Data tersimpan tanpa memuat ulang halaman.</p>
        </div>
        <button type="button" class="btn btn-primary" id="addBtn">
            <i class="bi bi-plus-lg me-1"></i> Tambah Pendaftar
        </button>
    </div>

    <div id="alert-container" aria-live="polite"></div>
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
            <span class="fw-semibold">
                Daftar Pendaftar <span class="badge text-bg-light border ms-1" id="total-count">0</span>
            </span>
            <div class="input-group" style="max-width: 300px">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="search" id="search" class="form-control border-start-0" placeholder="Cari pendaftar..." aria-label="Cari pendaftar">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover" id="pendaftaran-table">
                <thead>
                    <tr>
                        <th scope="col" style="width: 5%">No</th>
                        <th scope="col">Nama</th>
                        <th scope="col">Asal Sekolah</th>
                        <th scope="col">Prodi Tujuan</th>
                        <th scope="col">Kontak</th>
                        <th scope="col">Berkas</th>
                        <th scope="col" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Memuat data...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="pendaftaranModal" tabindex="-1" aria-labelledby="pendaftaranModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pendaftaranModalLabel">Tambah Pendaftar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <form id="pendaftaranForm">
                        @csrf
                        <input type="hidden" name="id" id="id">
                        @foreach ([
                            'nama' => ['label' => 'Nama Lengkap', 'type' => 'text', 'placeholder' => 'Nama pendaftar'],
                            'asal_sekolah' => ['label' => 'Asal Sekolah', 'type' => 'text', 'placeholder' => 'Contoh: SMA Negeri 1 Bandung'],
                            'prodi_tujuan' => ['label' => 'Prodi Tujuan', 'type' => 'text', 'placeholder' => 'Contoh: Informatika'],
                            'email' => ['label' => 'Email', 'type' => 'email', 'placeholder' => 'nama@email.com'],
                            'no_hp' => ['label' => 'No HP', 'type' => 'tel', 'placeholder' => 'Contoh: 081234567890'],
                        ] as $name => $field)
                            <div class="mb-3">
                                <label for="{{ $name }}" class="form-label">
                                    {{ $field['label'] }} <span class="text-danger">*</span>
                                </label>
                                <input type="{{ $field['type'] }}" class="form-control" id="{{ $name }}" name="{{ $name }}" placeholder="{{ $field['placeholder'] }}" required>
                                <div class="invalid-feedback" id="{{ $name }}-error"></div>
                            </div>
                        @endforeach
                        <div class="mb-3">
                            <label for="berkas" class="form-label">
                                Berkas Pendukung <span class="text-danger" id="berkas-required">*</span>
                            </label>
                            <div class="small mb-2 d-none" id="berkas-current">
                                Berkas saat ini:
                                <a href="#" target="_blank" rel="noopener" id="berkas-current-link"></a>
                            </div>
                            <input type="file" class="form-control" id="berkas" name="berkas" accept=".jpg,.jpeg,.png,.pdf">
                            <div class="invalid-feedback" id="berkas-error"></div>
                            <div class="form-text" id="berkas-help">Format jpg, jpeg, png, atau pdf. Maksimal 2 MB.</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="saveBtn">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                        <span class="submit-text">Simpan</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script data-page-script>
(() => {
    const root = document.getElementById('pendaftaran-page');
    if (!root || root.dataset.initialized) return;
    root.dataset.initialized = 'true';

    const urls = {
        data: @json(route('pendaftaran.data')),
        store: @json(route('pendaftaran.store')),
        edit: @json(route('pendaftaran.edit', ':id')),
        update: @json(route('pendaftaran.update', ':id')),
        destroy: @json(route('pendaftaran.destroy', ':id')),
    };
    let records = [];
    const $root = $(root);
    const modalElement = root.querySelector('#pendaftaranModal');
    const pendaftaranModal = bootstrap.Modal.getOrCreateInstance(modalElement);

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            Accept: 'application/json',
        },
    });

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function showAlert(message, type = 'success') {
        const icon = type === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle';
        const alert = $(`
            <div class="alert alert-${type} alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                <i class="bi ${icon}"></i>
                <span>${escapeHtml(message)}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        `);
        $root.find('#alert-container').empty().append(alert);
        window.setTimeout(() => bootstrap.Alert.getOrCreateInstance(alert[0]).close(), 4000);
    }

    function clearErrors() {
        $root.find('.invalid-feedback').text('');
        $root.find('.is-invalid').removeClass('is-invalid');
    }

    function emptyRow(icon, message) {
        return `<tr><td colspan="7"><div class="empty-state"><i class="bi ${icon}"></i><p class="mt-2 mb-0">${message}</p></div></td></tr>`;
    }

    function fileLink(item) {
        if (!item.berkas_url) return '<span class="text-muted">-</span>';
        const fileName = item.nama_berkas || item.berkas || '';
        const isPdf = fileName.toLowerCase().endsWith('.pdf');
        const icon = isPdf ? 'bi-file-earmark-pdf text-danger' : 'bi-file-earmark-image text-primary';
        return `<a href="${escapeHtml(item.berkas_url)}" target="_blank" rel="noopener" class="small text-decoration-none"><i class="bi ${icon} me-1"></i>${escapeHtml(item.nama_berkas || 'Lihat berkas')}</a>`;
    }

    function renderTable() {
        const keyword = $root.find('#search').val().trim().toLowerCase();
        const rows = records.filter((item) =>
            [item.nama, item.asal_sekolah, item.prodi_tujuan, item.email, item.no_hp]
                .some((value) => String(value ?? '').toLowerCase().includes(keyword))
        );

        $root.find('#total-count').text(records.length);
        if (records.length === 0) {
            $root.find('#pendaftaran-table tbody').html(emptyRow('bi-inbox', 'Belum ada pendaftar. Klik <strong>Tambah Pendaftar</strong> untuk memulai.'));
            return;
        }
        if (rows.length === 0) {
            $root.find('#pendaftaran-table tbody').html(emptyRow('bi-search', 'Tidak ada pendaftar yang cocok dengan pencarian.'));
            return;
        }

        const html = rows.map((item, index) => `
            <tr>
                <td class="text-muted">${index + 1}</td>
                <td class="fw-medium">${escapeHtml(item.nama)}</td>
                <td>${escapeHtml(item.asal_sekolah)}</td>
                <td><span class="badge text-bg-light border">${escapeHtml(item.prodi_tujuan)}</span></td>
                <td class="small"><div>${escapeHtml(item.email)}</div><div>${escapeHtml(item.no_hp)}</div></td>
                <td>${fileLink(item)}</td>
                <td class="text-end text-nowrap">
                    <button type="button" data-id="${Number(item.id)}" class="edit btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Edit</button>
                    <button type="button" data-id="${Number(item.id)}" data-nama="${escapeHtml(item.nama)}" class="delete btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Hapus</button>
                </td>
            </tr>
        `).join('');
        $root.find('#pendaftaran-table tbody').html(html);
    }

    function loadData() {
        $root.find('#pendaftaran-table tbody').html(`
            <tr><td colspan="7" class="text-center text-muted py-5">
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Memuat data...
            </td></tr>
        `);
        return $.ajax({ url: urls.data, type: 'GET', dataType: 'json', timeout: 15000 })
            .done((data) => {
                if (!Array.isArray(data)) {
                    console.error('Respons data pendaftaran bukan berupa daftar:', data);
                    $root.find('#pendaftaran-table tbody').html(emptyRow('bi-exclamation-triangle', 'Format data tidak sesuai. Silakan coba lagi.'));
                    return;
                }
                records = data;
                renderTable();
            })
            .fail((xhr) => {
                console.error('Gagal memuat data pendaftaran:', xhr.status, xhr.responseText);
                records = [];
                $root.find('#total-count').text('0');
                $root.find('#pendaftaran-table tbody').html(emptyRow('bi-wifi-off', 'Gagal memuat data. Periksa koneksi dan coba lagi.'));
            });
    }

    function setFileMode(item = null) {
        $root.find('#berkas').val('');
        $root.find('#berkas-required').toggleClass('d-none', item !== null);
        $root.find('#berkas-current').toggleClass('d-none', !item?.berkas_url);
        $root.find('#berkas-help').text(item
            ? 'Kosongkan jika tidak ingin mengganti berkas. Format jpg, jpeg, png, atau pdf. Maksimal 2 MB.'
            : 'Format jpg, jpeg, png, atau pdf. Maksimal 2 MB.');
        if (item?.berkas_url) {
            $root.find('#berkas-current-link').attr('href', item.berkas_url).text(item.nama_berkas || 'Lihat berkas');
        } else {
            $root.find('#berkas-current-link').removeAttr('href').text('');
        }
    }

    loadData();
    $root.on('input.pendaftaran', '#search', renderTable);
    $root.on('shown.bs.modal', '#pendaftaranModal', () => $root.find('#nama').trigger('focus'));
    $root.on('hidden.bs.modal', '#pendaftaranModal', () => {
        $root.find('#pendaftaranForm').trigger('reset');
        $root.find('#id').val('');
        clearErrors();
        setFileMode();
    });
    $root.on('click.pendaftaran', '#addBtn', () => {
        $root.find('#pendaftaranModalLabel').text('Tambah Pendaftar');
        setFileMode();
        pendaftaranModal.show();
    });
    $root.on('submit.pendaftaran', '#pendaftaranForm', (event) => {
        event.preventDefault();
        $root.find('#saveBtn').trigger('click');
    });
    $root.on('click.pendaftaran', '#saveBtn', function () {
        const button = $(this);
        const id = $root.find('#id').val();
        const formData = new FormData($root.find('#pendaftaranForm')[0]);
        if (id) formData.append('_method', 'PUT');

        button.prop('disabled', true).find('.submit-text').text('Menyimpan...');
        button.find('.spinner-border').removeClass('d-none');
        clearErrors();

        $.ajax({
            url: id ? urls.update.replace(':id', encodeURIComponent(id)) : urls.store,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
        }).done((response) => {
            pendaftaranModal.hide();
            loadData();
            showAlert(response.success);
        }).fail((xhr) => {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                $.each(xhr.responseJSON.errors, (key, messages) => {
                    $root.find(`#${CSS.escape(key)}`).addClass('is-invalid');
                    $root.find(`#${CSS.escape(key)}-error`).text(messages[0]);
                });
                $root.find('.is-invalid').first().trigger('focus');
                return;
            }
            console.error('Gagal menyimpan pendaftar:', xhr.status, xhr.responseText);
            showAlert('Data gagal disimpan. Silakan coba lagi.', 'danger');
        }).always(() => {
            button.prop('disabled', false).find('.submit-text').text('Simpan');
            button.find('.spinner-border').addClass('d-none');
        });
    });

    $root.on('click.pendaftaran', '.edit', function () {
        const id = $(this).data('id');
        $.getJSON(urls.edit.replace(':id', encodeURIComponent(id)))
            .done((data) => {
                $root.find('#pendaftaranModalLabel').text('Edit Pendaftar');
                $root.find('#id').val(data.id);
                $root.find('#nama').val(data.nama);
                $root.find('#asal_sekolah').val(data.asal_sekolah);
                $root.find('#prodi_tujuan').val(data.prodi_tujuan);
                $root.find('#email').val(data.email);
                $root.find('#no_hp').val(data.no_hp);
                setFileMode(data);
                pendaftaranModal.show();
            })
            .fail((xhr) => {
                console.error('Gagal memuat pendaftar untuk diedit:', xhr.status, xhr.responseText);
                showAlert('Gagal memuat data untuk diedit.', 'danger');
            });
    });

    $root.on('click.pendaftaran', '.delete', function () {
        const id = $(this).data('id');
        const nama = $(this).data('nama');
        window.confirmDelete(`Data pendaftar "${nama}" akan dihapus dan tidak bisa dikembalikan.`)
            .then((confirmed) => {
                if (!confirmed) return;
                $.ajax({
                    type: 'DELETE',
                    url: urls.destroy.replace(':id', encodeURIComponent(id)),
                    dataType: 'json',
                }).done((response) => {
                    loadData();
                    showAlert(response.success);
                }).fail((xhr) => {
                    console.error('Gagal menghapus pendaftar:', xhr.status, xhr.responseText);
                    showAlert('Gagal menghapus data. Silakan coba lagi.', 'danger');
                });
            });
    });
})();
</script>
@endpush
