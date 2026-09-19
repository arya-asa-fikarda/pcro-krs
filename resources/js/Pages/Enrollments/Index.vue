<script setup>
import { ref, watch } from 'vue';
import { router, Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    enrollments: Object,
    filters: Object,
    flash: Object,
});

const search = ref(props.filters.search || '');
const semester = ref(props.filters.semester || '');
const status = ref(props.filters.status || '');
const matchMode = ref(props.filters.match_mode || 'AND');
const perPage = ref(props.filters.per_page || 10);
const sortField = ref(props.filters.sort_field || 'created_at');
const sortDirection = ref(props.filters.sort_direction || 'desc');

const showCreateModal = ref(false);

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

let debounceTimer = null;

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

const submitCreate = () => {
    form.post('/enrollments', {
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        },
    });
};

watch([semester, status, matchMode, perPage], () => updateParams());
</script>

<template>
    <div class="container py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h3 font-weight-bold text-primary mb-1">Sistem KRS Akademik</h2>
                <p class="text-muted small mb-0">Single Page CRUD & High-Volume Data Management (5 Juta Row Ready)</p>
            </div>
            <!-- Button Trigger Modal Create -->
            <button @click="showCreateModal = true" class="btn btn-primary fw-bold shadow-sm">
                + Tambah KRS Baru (Atomic 3-Table)
            </button>
        </div>

        <!-- Flash Message Notification -->
        <div v-if="$page.props.flash?.success" class="alert alert-success alert-dismissible fade show" role="alert">
            {{ $page.props.flash.success }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <div v-if="$page.props.flash?.error" class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $page.props.flash.error }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

        <!-- Filter & Search Panel -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-secondary">Live Search (NIM / Nama / Kode
                            MK)</label>
                        <input v-model="search" @input="onSearchInput" type="text" class="form-control"
                            placeholder="Cari NIM, Nama, atau Kode MK..." />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold small text-secondary">Semester</label>
                        <select v-model="semester" class="form-select">
                            <option value="">Semua Semester</option>
                            <option value="GANJIL">GANJIL</option>
                            <option value="GENAP">GENAP</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold small text-secondary">Status KRS</label>
                        <select v-model="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="DRAFT">DRAFT</option>
                            <option value="SUBMITTED">SUBMITTED</option>
                            <option value="APPROVED">APPROVED</option>
                            <option value="REJECTED">REJECTED</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold small text-secondary">Logika Filter (TS-10)</label>
                        <select v-model="matchMode" class="form-select">
                            <option value="AND">AND (Semua Cocok)</option>
                            <option value="OR">OR (Salah Satu Cocok)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold small text-secondary">Baris / Halaman</label>
                        <select v-model="perPage" class="form-select">
                            <option :value="10">10 Data</option>
                            <option :value="25">25 Data</option>
                            <option :value="50">50 Data</option>
                            <option :value="100">100 Data</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Panel -->
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th @click="handleSort('student_nim')" style="cursor: pointer;">
                                NIM <span v-if="sortField === 'student_nim'">{{ sortDirection === 'asc' ? '▲' : '▼'
                                    }}</span>
                            </th>
                            <th @click="handleSort('student_name')" style="cursor: pointer;">
                                Nama Mahasiswa <span v-if="sortField === 'student_name'">{{ sortDirection === 'asc' ?
                                    '▲' : '▼' }}</span>
                            </th>
                            <th @click="handleSort('course_code')" style="cursor: pointer;">
                                Kode MK <span v-if="sortField === 'course_code'">{{ sortDirection === 'asc' ? '▲' : '▼'
                                    }}</span>
                            </th>
                            <th @click="handleSort('course_name')" style="cursor: pointer;">
                                Nama Mata Kuliah <span v-if="sortField === 'course_name'">{{ sortDirection === 'asc' ?
                                    '▲' : '▼' }}</span>
                            </th>
                            <th @click="handleSort('academic_year')" style="cursor: pointer;">Tahun Ajaran</th>
                            <th @click="handleSort('semester')" style="cursor: pointer;">Semester</th>
                            <th @click="handleSort('status')" style="cursor: pointer;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in enrollments.data" :key="item.id">
                            <td><code>{{ item.student?.nim }}</code></td>
                            <td class="fw-medium">{{ item.student?.name }}</td>
                            <td><span class="badge bg-secondary">{{ item.course?.code }}</span></td>
                            <td>{{ item.course?.name }}</td>
                            <td>{{ item.academic_year }}</td>
                            <td><span class="badge bg-outline-primary border text-primary">{{ item.semester }}</span>
                            </td>
                            <td>
                                <span class="badge" :class="{
                                    'bg-warning text-dark': item.status === 'DRAFT',
                                    'bg-info text-dark': item.status === 'SUBMITTED',
                                    'bg-success': item.status === 'APPROVED',
                                    'bg-danger': item.status === 'REJECTED'
                                }">{{ item.status }}</span>
                            </td>
                        </tr>
                        <tr v-if="enrollments.data.length === 0">
                            <td colspan="7" class="text-center py-5 text-muted">Tidak ada data KRS yang cocok.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
                <small class="text-muted">
                    Menampilkan <strong>{{ enrollments.from || 0 }}</strong> sampai <strong>{{ enrollments.to || 0
                        }}</strong> dari total <strong>{{ enrollments.total?.toLocaleString() }}</strong> KRS
                </small>
                <div class="btn-group">
                    <Link v-for="link in enrollments.links" :key="link.label" :href="link.url || '#'" class="btn btn-sm"
                        :class="link.active ? 'btn-primary' : 'btn-outline-secondary'" v-html="link.label" />
                </div>
            </div>
        </div>

        <!-- Modal Create KRS (TS-02 & TS-03) -->
        <div v-if="showCreateModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold">Tambah KRS Baru (3-Table Atomic Insert)</h5>
                        <button @click="showCreateModal = false" type="button"
                            class="btn-close btn-close-white"></button>
                    </div>
                    <form @submit.prevent="submitCreate">
                        <div class="modal-body">
                            <h6 class="fw-bold text-primary mb-3">1. Data Mahasiswa (students)</h6>
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">NIM (8-12 Angka)</label>
                                    <input v-model="form.student_nim" type="text" class="form-control"
                                        :class="{ 'is-invalid': form.errors.student_nim }" placeholder="2026001001" />
                                    <div v-if="form.errors.student_nim" class="invalid-feedback">{{
                                        form.errors.student_nim }}</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Nama Mahasiswa</label>
                                    <input v-model="form.student_name" type="text" class="form-control"
                                        :class="{ 'is-invalid': form.errors.student_name }" placeholder="Budi Santoso" />
                                    <div v-if="form.errors.student_name" class="invalid-feedback">{{
                                        form.errors.student_name }}</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Email</label>
                                    <input v-model="form.student_email" type="email" class="form-control"
                                        :class="{ 'is-invalid': form.errors.student_email }"
                                        placeholder="budi@example.com" />
                                    <div v-if="form.errors.student_email" class="invalid-feedback">{{
                                        form.errors.student_email }}</div>
                                </div>
                            </div>

                            <h6 class="fw-bold text-primary mb-3">2. Data Mata Kuliah (courses)</h6>
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Kode MK (Misal: IF101)</label>
                                    <input v-model="form.course_code" type="text" class="form-control"
                                        :class="{ 'is-invalid': form.errors.course_code }" placeholder="IF101" />
                                    <div v-if="form.errors.course_code" class="invalid-feedback">{{
                                        form.errors.course_code }}</div>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold">Nama Mata Kuliah</label>
                                    <input v-model="form.course_name" type="text" class="form-control"
                                        :class="{ 'is-invalid': form.errors.course_name }"
                                        placeholder="Pemrograman Web Lanjut" />
                                    <div v-if="form.errors.course_name" class="invalid-feedback">{{
                                        form.errors.course_name }}</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">SKS (1-6)</label>
                                    <input v-model="form.course_credits" type="number" min="1" max="6"
                                        class="form-control" :class="{ 'is-invalid': form.errors.course_credits }" />
                                    <div v-if="form.errors.course_credits" class="invalid-feedback">{{
                                        form.errors.course_credits }}</div>
                                </div>
                            </div>

                            <h6 class="fw-bold text-primary mb-3">3. Data Pengambilan KRS (enrollments)</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Tahun Ajaran (YYYY/YYYY)</label>
                                    <input v-model="form.academic_year" type="text" class="form-control"
                                        :class="{ 'is-invalid': form.errors.academic_year }" placeholder="2025/2026" />
                                    <div v-if="form.errors.academic_year" class="invalid-feedback">{{
                                        form.errors.academic_year }}</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Semester</label>
                                    <select v-model="form.semester" class="form-select"
                                        :class="{ 'is-invalid': form.errors.semester }">
                                        <option value="GANJIL">GANJIL</option>
                                        <option value="GENAP">GENAP</option>
                                    </select>
                                    <div v-if="form.errors.semester" class="invalid-feedback">{{ form.errors.semester }}
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Status KRS</label>
                                    <select v-model="form.status" class="form-select"
                                        :class="{ 'is-invalid': form.errors.status }">
                                        <option value="DRAFT">DRAFT</option>
                                        <option value="SUBMITTED">SUBMITTED</option>
                                        <option value="APPROVED">APPROVED</option>
                                        <option value="REJECTED">REJECTED</option>
                                    </select>
                                    <div v-if="form.errors.status" class="invalid-feedback">{{ form.errors.status }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button @click="showCreateModal = false" type="button"
                                class="btn btn-secondary">Batal</button>
                            <button type="submit" :disabled="form.processing" class="btn btn-primary fw-bold">
                                {{ form.processing ? 'Menyimpan...' : 'Simpan KRS (Atomic Transaction)' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
