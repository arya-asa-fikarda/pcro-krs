<script setup>
import { ref, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';

const props = defineProps({
    enrollments: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const semester = ref(props.filters.semester || '');
const status = ref(props.filters.status || '');
const matchMode = ref(props.filters.match_mode || 'AND');
const perPage = ref(props.filters.per_page || 10);
const sortField = ref(props.filters.sort_field || 'created_at');
const sortDirection = ref(props.filters.sort_direction || 'desc');

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

watch([semester, status, matchMode, perPage], () => updateParams());
</script>

<template>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h3 font-weight-bold text-primary mb-1">Sistem KRS Akademik</h2>
                <p class="text-muted small mb-0">Single Page CRUD & High-Volume Data Management (5 Juta Row Ready)</p>
            </div>
        </div>

        <!-- Filter & Search Panel -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <!-- TS-08: Live Search (Debounced 400ms) -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-secondary">Live Search (NIM / Nama / Kode
                            MK)</label>
                        <input v-model="search" @input="onSearchInput" type="text" class="form-control"
                            placeholder="Cari NIM, Nama, atau Kode MK..." />
                    </div>

                    <!-- TS-07: Quick Filter Semester -->
                    <div class="col-md-2">
                        <label class="form-label fw-bold small text-secondary">Semester</label>
                        <select v-model="semester" class="form-select">
                            <option value="">Semua Semester</option>
                            <option value="GANJIL">GANJIL</option>
                            <option value="GENAP">GENAP</option>
                        </select>
                    </div>

                    <!-- TS-07: Quick Filter Status -->
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

                    <!-- TS-10: Advanced Multi-Filter Logic (AND / OR) -->
                    <div class="col-md-2">
                        <label class="form-label fw-bold small text-secondary">Logika Filter (TS-10)</label>
                        <select v-model="matchMode" class="form-select">
                            <option value="AND">AND (Semua Cocok)</option>
                            <option value="OR">OR (Salah Satu Cocok)</option>
                        </select>
                    </div>

                    <!-- TS-05: Page Size Selector -->
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

        <!-- Data Table Panel -->
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <!-- TS-06: Header Sorting -->
                            <th @click="handleSort('student_nim')" style="cursor: pointer;" class="user-select-none">
                                NIM <span v-if="sortField === 'student_nim'">{{ sortDirection === 'asc' ? '▲' : '▼'
                                    }}</span>
                            </th>
                            <th @click="handleSort('student_name')" style="cursor: pointer;" class="user-select-none">
                                Nama Mahasiswa <span v-if="sortField === 'student_name'">{{ sortDirection === 'asc' ?
                                    '▲' : '▼' }}</span>
                            </th>
                            <th @click="handleSort('course_code')" style="cursor: pointer;" class="user-select-none">
                                Kode MK <span v-if="sortField === 'course_code'">{{ sortDirection === 'asc' ? '▲' : '▼'
                                    }}</span>
                            </th>
                            <th @click="handleSort('course_name')" style="cursor: pointer;" class="user-select-none">
                                Nama Mata Kuliah <span v-if="sortField === 'course_name'">{{ sortDirection === 'asc' ?
                                    '▲' : '▼' }}</span>
                            </th>
                            <th @click="handleSort('academic_year')" style="cursor: pointer;" class="user-select-none">
                                Tahun Ajaran</th>
                            <th @click="handleSort('semester')" style="cursor: pointer;" class="user-select-none">
                                Semester</th>
                            <th @click="handleSort('status')" style="cursor: pointer;" class="user-select-none">Status
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in enrollments.data" :key="item.id">
                            <td><code>{{ item.student?.nim }}</code></td>
                            <td class="fw-medium">{{ item.student?.name }}</td>
                            <td><span class="badge bg-secondary">{{ item.course?.code }}</span></td>
                            <td>{{ item.course?.name }}</td>
                            <td>{{ item.academic_year }}</td>
                            <td>
                                <span class="badge bg-outline-primary border text-primary">{{ item.semester }}</span>
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
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                Tidak ada data KRS yang cocok dengan kriteria pencarian/filter.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- TS-05: Server-side Pagination Control -->
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
    </div>
</template>
