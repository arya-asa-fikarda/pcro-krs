<script setup>
import { ref, watch, onMounted } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    enrollments: Object,
    filters: Object,
    flash: Object,
    errors: Object,
});

// --- STATE MANAGEMENT ---
const search = ref(props.filters.search || '');
const semester = ref(props.filters.semester || '');
const status = ref(props.filters.status || '');
const matchMode = ref(props.filters.match_mode || 'AND');
const perPage = ref(props.filters.per_page || 10);
const sortField = ref(props.filters.sort_field || 'created_at');
const sortDirection = ref(props.filters.sort_direction || 'desc');

const showCreateModal = ref(false);
const showEditModal = ref(false);
const selectedEnrollment = ref(null);

// --- THEME SWITCHER LOGIC (SYSTEM / LIGHT / DARK) ---
const themeMode = ref(localStorage.getItem('krs_theme_mode') || 'system');
const activeTheme = ref('light');

const applyTheme = () => {
    let isDark = false;
    if (themeMode.value === 'system') {
        isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    } else {
        isDark = themeMode.value === 'dark';
    }
    activeTheme.value = isDark ? 'dark' : 'light';
    document.documentElement.setAttribute('data-bs-theme', activeTheme.value);
    localStorage.setItem('krs_theme_mode', themeMode.value);
};

const setTheme = (mode) => {
    themeMode.value = mode;
    applyTheme();
};

onMounted(() => {
    applyTheme();
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (themeMode.value === 'system') applyTheme();
    });
});

// --- FORMS ---
const form = useForm({
    student_nim: '',
    student_name: '',
    student_email: '',
    course_code: '',
    course_name: '',
    course_credits: 3,
    academic_year: '2025/2026',
    semester: 'GANJIL',
    status: 'DRAFT',
});

const editForm = useForm({
    academic_year: '',
    semester: '',
    status: '',
});

let debounceTimer = null;

// --- ACTIONS ---
const updateParams = () => {
    router.get(
        '/enrollments',
        {
            search: search.value,
            semester: semester.value,
            status: status.value,
            match_mode: matchMode.value,
            per_page: perPage.value,
            sort_field: sortField.value,
            sort_direction: sortDirection.value,
        },
        { preserveState: true, replace: true }
    );
};

const onSearchInput = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        updateParams();
    }, 400);
};

const handleSort = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
    updateParams();
};

const openCreateModal = () => {
    form.clearErrors();
    showCreateModal.value = true;
};

const submitCreate = () => {
    form.post('/enrollments', {
        preserveScroll: true,
        onError: () => {
            showCreateModal.value = true;
        },
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
            form.clearErrors();
        },
    });
};

const openEditModal = (item) => {
    editForm.clearErrors();
    selectedEnrollment.value = item;
    editForm.academic_year = item.academic_year;
    editForm.semester = item.semester;
    editForm.status = item.status;
    showEditModal.value = true;
};

const submitUpdate = () => {
    editForm.put(`/enrollments/${selectedEnrollment.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            editForm.clearErrors();
        },
    });
};

const deleteEnrollment = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus data KRS ini?')) {
        router.delete(`/enrollments/${id}`, { preserveScroll: true });
    }
};

watch([semester, status, matchMode, perPage], () => updateParams());
</script>

<template>
    <div class="app-layout min-vh-100 py-4">
        <div class="container-fluid px-3 px-lg-5">

            <!-- HEADER BAR -->
            <header
                class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 pb-3 mb-4 border-bottom">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge badge-version">v1.0 SPA</span>
                        <h1 class="h3 font-semibold tracking-tight text-body mb-0">Portal KRS Akademik</h1>
                    </div>
                    <p class="text-secondary small mb-0">High-Volume Record Management Engine &bull; 5,000,000+ Rows</p>
                </div>

                <!-- RIGHT CONTROLS -->
                <div class="d-flex flex-wrap align-items-center gap-2">

                    <!-- Theme Toggle -->
                    <div class="theme-switcher-group btn-group p-1 border rounded-3" role="group">
                        <button type="button" @click="setTheme('light')"
                            class="btn btn-sm border-0 rounded-2 py-1 px-2.5"
                            :class="themeMode === 'light' ? 'btn-primary shadow-sm' : 'text-secondary'"
                            title="Light Mode">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="4" />
                                <path d="M12 2v2" />
                                <path d="M12 20v2" />
                                <path d="m4.93 4.93 1.41 1.41" />
                                <path d="m17.66 17.66 1.41 1.41" />
                                <path d="M2 12h2" />
                                <path d="M20 12h2" />
                                <path d="m6.34 17.66-1.41 1.41" />
                                <path d="m19.07 4.93-1.41 1.41" />
                            </svg>
                        </button>
                        <button type="button" @click="setTheme('dark')"
                            class="btn btn-sm border-0 rounded-2 py-1 px-2.5"
                            :class="themeMode === 'dark' ? 'btn-primary shadow-sm' : 'text-secondary'"
                            title="Dark Mode">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z" />
                            </svg>
                        </button>
                        <button type="button" @click="setTheme('system')"
                            class="btn btn-sm border-0 rounded-2 py-1 px-2.5"
                            :class="themeMode === 'system' ? 'btn-primary shadow-sm' : 'text-secondary'"
                            title="System Default">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2">
                                <rect width="20" height="14" x="2" y="3" rx="2" />
                                <line x1="8" x2="16" y1="21" y2="21" />
                                <line x1="12" x2="12" y1="17" y2="21" />
                            </svg>
                        </button>
                    </div>

                    <!-- Export Button -->
                    <a :href="`/enrollments/export?search=${search}&semester=${semester}&status=${status}&match_mode=${matchMode}`"
                        class="btn btn-outline-secondary btn-sm fw-medium d-inline-flex align-items-center gap-1.5 rounded-3 shadow-sm"
                        target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                            <polyline points="7 10 12 15 17 10" />
                            <line x1="12" x2="12" y1="15" y2="3" />
                        </svg>
                        Export CSV
                    </a>

                    <!-- Add Button -->
                    <button @click="openCreateModal"
                        class="btn btn-primary btn-sm fw-semibold d-inline-flex align-items-center gap-1 rounded-3 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5">
                            <line x1="12" x2="12" y1="5" y2="19" />
                            <line x1="5" x2="19" y1="12" y2="12" />
                        </svg>
                        Tambah KRS
                    </button>
                </div>
            </header>

            <!-- FLASH ALERTS -->
            <div v-if="$page.props.flash?.success"
                class="alert alert-success border-0 rounded-3 shadow-sm mb-4 d-flex align-items-center justify-content-between p-3"
                role="alert">
                <div class="d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                        <polyline points="22 4 12 14.01 9 11.01" />
                    </svg>
                    <span><strong>Berhasil!</strong> {{ $page.props.flash.success }}</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>

            <div v-if="$page.props.flash?.error && !showCreateModal && !showEditModal"
                class="alert alert-danger border-0 rounded-3 shadow-sm mb-4 d-flex align-items-center justify-content-between p-3"
                role="alert">
                <div class="d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" x2="12" y1="8" y2="12" />
                        <line x1="12" x2="12.01" y1="16" y2="16" />
                    </svg>
                    <span><strong>Gagal!</strong> {{ $page.props.flash.error }}</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>

            <!-- FILTER PANEL -->
            <div class="card border rounded-3 shadow-sm mb-4 card-surface">
                <div class="card-body p-3">
                    <div class="row g-2.5">
                        <div class="col-12 col-md-4">
                            <label class="form-label text-uppercase text-secondary fw-bold tracking-wider mb-1"
                                style="font-size: 0.68rem;">Pencarian (NIM / Nama / Kode MK)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text border-end-0 text-secondary"><svg
                                        xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="11" cy="11" r="8" />
                                        <path d="m21 21-4.3-4.3" />
                                    </svg></span>
                                <input v-model="search" @input="onSearchInput" type="text"
                                    class="form-control border-start-0 ps-0" placeholder="Ketik kata kunci..." />
                            </div>
                        </div>
                        <div class="col-6 col-sm-6 col-md-2">
                            <label class="form-label text-uppercase text-secondary fw-bold tracking-wider mb-1"
                                style="font-size: 0.68rem;">Semester</label>
                            <select v-model="semester" class="form-select form-select-sm">
                                <option value="">Semua Semester</option>
                                <option value="GANJIL">GANJIL</option>
                                <option value="GENAP">GENAP</option>
                            </select>
                        </div>
                        <div class="col-6 col-sm-6 col-md-2">
                            <label class="form-label text-uppercase text-secondary fw-bold tracking-wider mb-1"
                                style="font-size: 0.68rem;">Status KRS</label>
                            <select v-model="status" class="form-select form-select-sm">
                                <option value="">Semua Status</option>
                                <option value="DRAFT">DRAFT</option>
                                <option value="SUBMITTED">SUBMITTED</option>
                                <option value="APPROVED">APPROVED</option>
                                <option value="REJECTED">REJECTED</option>
                            </select>
                        </div>
                        <div class="col-6 col-sm-6 col-md-2">
                            <label class="form-label text-uppercase text-secondary fw-bold tracking-wider mb-1"
                                style="font-size: 0.68rem;">Logika Filter (TS-10)</label>
                            <select v-model="matchMode" class="form-select form-select-sm">
                                <option value="AND">AND (Semua Cocok)</option>
                                <option value="OR">OR (Salah Satu)</option>
                            </select>
                        </div>
                        <div class="col-6 col-sm-6 col-md-2">
                            <label class="form-label text-uppercase text-secondary fw-bold tracking-wider mb-1"
                                style="font-size: 0.68rem;">Baris / Hal</label>
                            <select v-model="perPage" class="form-select form-select-sm">
                                <option :value="10">10 Baris</option>
                                <option :value="25">25 Baris</option>
                                <option :value="50">50 Baris</option>
                                <option :value="100">100 Baris</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MAIN DATA TABLE CARD -->
            <div class="card border rounded-3 shadow-sm card-surface overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-nowrap">
                        <thead class="border-bottom">
                            <tr class="text-uppercase text-secondary fw-bold tracking-wider"
                                style="font-size: 0.72rem;">
                                <th @click="handleSort('student_nim')" class="py-3 px-3 user-select-none"
                                    style="cursor: pointer;">
                                    NIM <span v-if="sortField === 'student_nim'" class="text-primary">{{ sortDirection
                                        === 'asc' ? '▲' : '▼' }}</span>
                                </th>
                                <th @click="handleSort('student_name')" class="py-3 px-3 user-select-none"
                                    style="cursor: pointer;">
                                    Mahasiswa <span v-if="sortField === 'student_name'" class="text-primary">{{
                                        sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                </th>
                                <th @click="handleSort('course_code')" class="py-3 px-3 user-select-none"
                                    style="cursor: pointer;">
                                    Kode <span v-if="sortField === 'course_code'" class="text-primary">{{ sortDirection
                                        === 'asc' ? '▲' : '▼' }}</span>
                                </th>
                                <th @click="handleSort('course_name')" class="py-3 px-3 user-select-none"
                                    style="cursor: pointer;">
                                    Mata Kuliah <span v-if="sortField === 'course_name'" class="text-primary">{{
                                        sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                </th>
                                <th @click="handleSort('academic_year')" class="py-3 px-3 user-select-none"
                                    style="cursor: pointer;">Tahun Ajaran</th>
                                <th @click="handleSort('semester')" class="py-3 px-3 user-select-none"
                                    style="cursor: pointer;">Semester</th>
                                <th @click="handleSort('status')" class="py-3 px-3 user-select-none"
                                    style="cursor: pointer;">Status</th>
                                <th class="text-center py-3 px-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in enrollments.data" :key="item.id">
                                <td class="px-3"><code
                                        class="font-mono nim-code px-1.5 py-0.5 rounded border">{{ item.student?.nim }}</code>
                                </td>
                                <td class="px-3 fw-semibold text-body">{{ item.student?.name }}</td>
                                <td class="px-3"><span class="badge badge-course-code font-mono">{{ item.course?.code
                                        }}</span></td>
                                <td class="px-3 text-secondary">{{ item.course?.name }}</td>
                                <td class="px-3 text-secondary small font-mono">{{ item.academic_year }}</td>
                                <td class="px-3"><span class="badge badge-semester font-mono">{{ item.semester }}</span>
                                </td>

                                <!-- TAILWIND/FILAMENT HIGH-CONTRAST BADGES (NO DOTS) -->
                                <td class="px-3">
                                    <span v-if="item.status === 'APPROVED'" class="status-badge status-approved">
                                        APPROVED
                                    </span>
                                    <span v-else-if="item.status === 'DRAFT'" class="status-badge status-draft">
                                        DRAFT
                                    </span>
                                    <span v-else-if="item.status === 'SUBMITTED'" class="status-badge status-submitted">
                                        SUBMITTED
                                    </span>
                                    <span v-else class="status-badge status-rejected">
                                        REJECTED
                                    </span>
                                </td>

                                <!-- EXPLICIT ACTION BUTTONS WITH CLEAR ICONS -->
                                <td class="text-center px-3">
                                    <div class="d-inline-flex gap-1">
                                        <button @click="openEditModal(item)" class="btn btn-action btn-action-edit"
                                            title="Edit Data KRS">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                                                stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
                                                <path d="m15 5 4 4" />
                                            </svg>
                                            <span>Edit</span>
                                        </button>
                                        <button @click="deleteEnrollment(item.id)"
                                            class="btn btn-action btn-action-delete" title="Hapus Data KRS">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                                                stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M3 6h18" />
                                                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                                            </svg>
                                            <span>Hapus</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="enrollments.data.length === 0">
                                <td colspan="8" class="text-center py-5 text-secondary">
                                    <div class="d-flex flex-column align-items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <circle cx="11" cy="11" r="8" />
                                            <path d="m21 21-4.3-4.3" />
                                        </svg>
                                        <span class="small">Tidak ada data KRS yang cocok.</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- FOOTER PAGINATION -->
                <div
                    class="card-footer border-top py-3 px-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                    <span class="text-secondary small text-center text-md-start">
                        Menampilkan <strong class="text-body">{{ enrollments.from || 0 }}</strong> &ndash; <strong
                            class="text-body">{{ enrollments.to || 0 }}</strong> dari <strong class="text-body">{{
                                enrollments.total?.toLocaleString() }}</strong> KRS
                    </span>
                    <div class="w-100 w-md-auto overflow-x-auto text-center text-md-end pb-1">
                        <div class="btn-group btn-group-sm d-inline-flex p-0.5 border rounded-2">
                            <Link v-for="link in enrollments.links" :key="link.label" :href="link.url || '#'"
                                class="btn btn-sm border-0 rounded-1"
                                :class="link.active ? 'btn-primary fw-bold shadow-sm' : 'text-secondary'"
                                v-html="link.label" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- FOOTER CREDENTIALS -->
            <footer class="text-center py-4 text-secondary small mt-3">
                Developed by <strong class="text-body">Arya Asa Fikarda</strong> &copy; 2026 &bull; Technical Test
                Submission
            </footer>
        </div>

        <!-- MODAL CREATE -->
        <div v-if="showCreateModal" class="modal fade show d-block" tabindex="-1"
            style="background: rgba(0,0,0,0.65); backdrop-filter: blur(2px);">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border shadow-lg rounded-3">
                    <div class="modal-header border-bottom px-4 py-3">
                        <h5 class="modal-title h6 font-semibold text-body mb-0">Tambah Data KRS Baru</h5>
                        <button @click="showCreateModal = false" type="button" class="btn-close"></button>
                    </div>
                    <form @submit.prevent="submitCreate">
                        <div class="modal-body p-4">
                            <div v-if="$page.props.flash?.error" class="alert alert-danger border-0 mb-4 p-3 small"
                                role="alert">
                                <strong>Gagal:</strong> {{ $page.props.flash.error }}
                            </div>

                            <div class="text-uppercase text-primary fw-bold tracking-wider mb-3"
                                style="font-size: 0.7rem;">1. Data Mahasiswa (students)</div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-medium text-secondary">NIM (8-12 Angka)</label>
                                    <input v-model="form.student_nim" type="text" inputmode="numeric" maxlength="12"
                                        @input="form.student_nim = form.student_nim.replace(/[^0-9]/g, '')"
                                        class="form-control form-control-sm font-mono"
                                        :class="{ 'is-invalid': form.errors.student_nim }" placeholder="2026001001" />
                                    <div v-if="form.errors.student_nim" class="invalid-feedback d-block small">{{
                                        form.errors.student_nim }}</div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-medium text-secondary">Nama Mahasiswa</label>
                                    <input v-model="form.student_name" type="text" class="form-control form-control-sm"
                                        :class="{ 'is-invalid': form.errors.student_name }"
                                        placeholder="Budi Santoso" />
                                    <div v-if="form.errors.student_name" class="invalid-feedback d-block small">{{
                                        form.errors.student_name }}</div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-medium text-secondary">Email</label>
                                    <input v-model="form.student_email" type="email"
                                        class="form-control form-control-sm"
                                        :class="{ 'is-invalid': form.errors.student_email }"
                                        placeholder="budi@example.com" />
                                    <div v-if="form.errors.student_email" class="invalid-feedback d-block small">{{
                                        form.errors.student_email }}</div>
                                </div>
                            </div>

                            <div class="text-uppercase text-primary fw-bold tracking-wider mb-3"
                                style="font-size: 0.7rem;">2. Data Mata Kuliah (courses)</div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-medium text-secondary">Kode MK (contoh:
                                        IF101)</label>
                                    <input v-model="form.course_code" type="text" maxlength="7"
                                        @input="form.course_code = form.course_code.toUpperCase()"
                                        class="form-control form-control-sm font-mono"
                                        :class="{ 'is-invalid': form.errors.course_code }" placeholder="IF101" />
                                    <div v-if="form.errors.course_code" class="invalid-feedback d-block small">{{
                                        form.errors.course_code }}</div>
                                </div>
                                <div class="col-12 col-md-5">
                                    <label class="form-label small fw-medium text-secondary">Nama Mata Kuliah</label>
                                    <input v-model="form.course_name" type="text" class="form-control form-control-sm"
                                        :class="{ 'is-invalid': form.errors.course_name }"
                                        placeholder="Pemrograman Web Lanjut" />
                                    <div v-if="form.errors.course_name" class="invalid-feedback d-block small">{{
                                        form.errors.course_name }}</div>
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label small fw-medium text-secondary">SKS (1-6)</label>
                                    <input v-model="form.course_credits" type="number" min="1" max="6"
                                        class="form-control form-control-sm"
                                        :class="{ 'is-invalid': form.errors.course_credits }" />
                                    <div v-if="form.errors.course_credits" class="invalid-feedback d-block small">{{
                                        form.errors.course_credits }}</div>
                                </div>
                            </div>

                            <div class="text-uppercase text-primary fw-bold tracking-wider mb-3"
                                style="font-size: 0.7rem;">3. Data Pengambilan KRS (enrollments)</div>
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-medium text-secondary">Tahun Ajaran</label>
                                    <input v-model="form.academic_year" type="text" maxlength="9"
                                        class="form-control form-control-sm font-mono"
                                        :class="{ 'is-invalid': form.errors.academic_year }" placeholder="2025/2026" />
                                    <div v-if="form.errors.academic_year" class="invalid-feedback d-block small">{{
                                        form.errors.academic_year }}</div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-medium text-secondary">Semester</label>
                                    <select v-model="form.semester" class="form-select form-select-sm">
                                        <option value="GANJIL">GANJIL</option>
                                        <option value="GENAP">GENAP</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-medium text-secondary">Status KRS</label>
                                    <select v-model="form.status" class="form-select form-select-sm">
                                        <option value="DRAFT">DRAFT</option>
                                        <option value="SUBMITTED">SUBMITTED</option>
                                        <option value="APPROVED">APPROVED</option>
                                        <option value="REJECTED">REJECTED</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-top px-4 py-3">
                            <button @click="showCreateModal = false" type="button"
                                class="btn btn-outline-secondary btn-sm rounded-2">Batal</button>
                            <button type="submit" :disabled="form.processing"
                                class="btn btn-primary btn-sm fw-medium rounded-2">
                                {{ form.processing ? 'Menyimpan...' : 'Simpan Transaksi KRS' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL EDIT -->
        <div v-if="showEditModal" class="modal fade show d-block" tabindex="-1"
            style="background: rgba(0,0,0,0.65); backdrop-filter: blur(2px);">
            <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border shadow-lg rounded-3">
                    <div class="modal-header border-bottom px-4 py-3">
                        <h5 class="modal-title h6 font-semibold text-body mb-0">Edit Periode & Status KRS</h5>
                        <button @click="showEditModal = false" type="button" class="btn-close"></button>
                    </div>
                    <form @submit.prevent="submitUpdate">
                        <div class="modal-body p-4">
                            <div v-if="$page.props.flash?.error" class="alert alert-danger border-0 mb-3 p-3 small"
                                role="alert">
                                <strong>Gagal:</strong> {{ $page.props.flash.error }}
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-medium text-secondary">Tahun Ajaran</label>
                                <input v-model="editForm.academic_year" type="text" maxlength="9"
                                    class="form-control form-control-sm font-mono"
                                    :class="{ 'is-invalid': editForm.errors.academic_year }" />
                                <div v-if="editForm.errors.academic_year" class="invalid-feedback d-block small">{{
                                    editForm.errors.academic_year }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-medium text-secondary">Semester</label>
                                <select v-model="editForm.semester" class="form-select form-select-sm">
                                    <option value="GANJIL">GANJIL</option>
                                    <option value="GENAP">GENAP</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-medium text-secondary">Status KRS</label>
                                <select v-model="editForm.status" class="form-select form-select-sm">
                                    <option value="DRAFT">DRAFT</option>
                                    <option value="SUBMITTED">SUBMITTED</option>
                                    <option value="APPROVED">APPROVED</option>
                                    <option value="REJECTED">REJECTED</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer border-top px-4 py-3">
                            <button @click="showEditModal = false" type="button"
                                class="btn btn-outline-secondary btn-sm rounded-2">Batal</button>
                            <button type="submit" :disabled="editForm.processing"
                                class="btn btn-primary btn-sm fw-medium rounded-2">
                                {{ editForm.processing ? 'Memperbarui...' : 'Simpan Perubahan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</template>

<style scoped>
/* GENERAL TYPOGRAPHY & LAYOUT */
.font-mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
}

.tracking-wider {
    letter-spacing: 0.05em;
}

.tracking-tight {
    letter-spacing: -0.025em;
}

/* CUSTOM HIGH-CONTRAST STATUS BADGES (NO DOTS) */
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.65rem;
    font-size: 0.7rem;
    font-weight: 700;
    font-family: ui-monospace, SFMono-Regular, monospace;
    border-radius: 6px;
    letter-spacing: 0.03em;
}

/* LIGHT MODE BADGES */
[data-bs-theme="light"] .status-approved {
    background-color: #dcfce7;
    color: #15803d;
    border: 1px solid #bbf7d0;
}

[data-bs-theme="light"] .status-draft {
    background-color: #fef3c7;
    color: #b45309;
    border: 1px solid #fde68a;
}

[data-bs-theme="light"] .status-submitted {
    background-color: #e0f2fe;
    color: #0369a1;
    border: 1px solid #bae6fd;
}

[data-bs-theme="light"] .status-rejected {
    background-color: #ffe4e6;
    color: #be123c;
    border: 1px solid #fecdd3;
}

/* DARK MODE BADGES (HIGH VISIBILITY & RICH CONTRAST) */
[data-bs-theme="dark"] .status-approved {
    background-color: rgba(21, 128, 61, 0.25);
    color: #4ade80;
    border: 1px solid rgba(74, 222, 128, 0.35);
}

[data-bs-theme="dark"] .status-draft {
    background-color: rgba(180, 83, 9, 0.25);
    color: #fbbf24;
    border: 1px solid rgba(251, 191, 36, 0.35);
}

[data-bs-theme="dark"] .status-submitted {
    background-color: rgba(3, 105, 161, 0.25);
    color: #38bdf8;
    border: 1px solid rgba(56, 189, 248, 0.35);
}

[data-bs-theme="dark"] .status-rejected {
    background-color: rgba(190, 18, 60, 0.25);
    color: #fb7185;
    border: 1px solid rgba(251, 113, 133, 0.35);
}

/* CUSTOM SLA/SLATE DARK MODE SURFACES */
[data-bs-theme="dark"] .app-layout {
    background-color: #0f172a !important;
    /* Deep Slate 900 */
}

[data-bs-theme="dark"] .card-surface,
[data-bs-theme="dark"] .modal-content {
    background-color: #1e293b !important;
    /* Rich Slate 800 */
    border-color: #334155 !important;
}

[data-bs-theme="dark"] .table {
    --bs-table-bg: #1e293b;
    --bs-table-hover-bg: #334155;
    color: #f1f5f9;
}

[data-bs-theme="dark"] .nim-code {
    background-color: #0f172a;
    border-color: #334155;
    color: #38bdf8;
}

[data-bs-theme="dark"] .badge-version {
    background-color: rgba(56, 189, 248, 0.15);
    color: #38bdf8;
    border: 1px solid rgba(56, 189, 248, 0.3);
}

[data-bs-theme="dark"] .badge-course-code,
[data-bs-theme="dark"] .badge-semester {
    background-color: #334155;
    color: #e2e8f0;
    border: 1px solid #475569;
}

/* LIGHT MODE SURFACES */
[data-bs-theme="light"] .app-layout {
    background-color: #f8fafc !important;
}

[data-bs-theme="light"] .nim-code {
    background-color: #f1f5f9;
    border-color: #e2e8f0;
    color: #0284c7;
}

[data-bs-theme="light"] .badge-version {
    background-color: #e0f2fe;
    color: #0369a1;
    border: 1px solid #bae6fd;
}

[data-bs-theme="light"] .badge-course-code,
[data-bs-theme="light"] .badge-semester {
    background-color: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

/* ACTION BUTTONS */
.btn-action {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.72rem;
    font-weight: 600;
    padding: 0.25rem 0.55rem;
    border-radius: 6px;
    border: 1px solid transparent;
    transition: all 0.15s ease-in-out;
}

/* EDIT BUTTON */
.btn-action-edit {
    background-color: rgba(2, 132, 199, 0.1);
    color: #0284c7;
    border-color: rgba(2, 132, 199, 0.2);
}

.btn-action-edit:hover {
    background-color: #0284c7;
    color: #ffffff;
}

/* DELETE BUTTON */
.btn-action-delete {
    background-color: rgba(220, 38, 38, 0.1);
    color: #dc2626;
    border-color: rgba(220, 38, 38, 0.2);
}

.btn-action-delete:hover {
    background-color: #dc2626;
    color: #ffffff;
}
</style>
