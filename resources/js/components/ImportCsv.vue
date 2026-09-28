<template>
  <div class="dashboard-shell import-shell">
    <aside class="sidebar">
      <div class="brand-row">
        <div class="brand-mark">✓</div>
        <div class="brand-name">SteadyTracker</div>
      </div>
 <nav class="sidebar-nav" aria-label="Main navigation">
        <button class="nav-item" type="button" @click="go('/dashboard')">
          <span class="nav-icon"><House :size="18" /></span>
          <span>Home</span>
        </button>
        <button class="nav-item active" type="button" aria-current="page" @click="go('/inbox')">
          <span class="nav-icon"><Mail :size="18" /></span>
          <span>Inbox</span>
        </button>
        <button class="nav-item" type="button" @click="go('/activities')">
          <span class="nav-icon"><CircleCheckBig :size="18" /></span>
          <span>My Tasks</span>
        </button>
        <button class="nav-item" type="button" @click="go('/import')">
          <span class="nav-icon"><Import :size="18" /></span>
          <span>Import</span>
        </button>
        <button class="nav-item" type="button" @click="go('/settings')">
          <span class="nav-icon"><Settings :size="18" /></span>
          <span>Settings</span>
        </button>
      </nav>

      <div class="home-sidebar-art" aria-hidden="true"></div>

      <div class="profile-card">
        <div class="avatar small">{{ userInitial }}</div>
        <span>{{ displayName }}</span>
      </div>
    </aside>

    <main class="dashboard-main home-main">
      <header class="topbar">
        <div class="page-heading-copy">
          <span class="eyebrow">Bulk actions</span>
          <h1>Import activities</h1>
        </div>
        <div class="topbar-actions">
          <div class="avatar large">{{ userInitial }}</div>
          <span class="home-user-name">{{ displayName }}</span>
        </div>
      </header>

      <div class="import-page">
        <section class="import-section" aria-labelledby="import-instructions">
          <div class="import-section-header">
            <div>
              <h2 id="import-instructions">Bring in your activities</h2>
              <p>Use a CSV file with one activity per row. Your activities stay private to your account.</p>
            </div>
            <button class="secondary-ghost template-button" type="button" @click="downloadTemplate">
              <Download :size="17" />
              <span>Download template</span>
            </button>
          </div>

          <div class="csv-columns">
            <span>Required</span>
            <code>title</code>
            <span>Optional</span>
            <code>description</code>
            <code>priority</code>
            <code>activity_status</code>
            <code>due_at</code>
          </div>

          <input
            ref="fileInput"
            class="visually-hidden"
            type="file"
            accept=".csv,text/csv"
            aria-label="Choose a CSV file"
            @change="handleFileChange"
          />

          <div
            class="csv-dropzone"
            :class="{ 'is-dragging': isDragging, 'has-file': selectedFile }"
            role="button"
            tabindex="0"
            @click="openFilePicker"
            @keydown.enter.prevent="openFilePicker"
            @keydown.space.prevent="openFilePicker"
            @dragenter.prevent="isDragging = true"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop"
          >
            <template v-if="selectedFile">
              <div class="csv-file-icon"><FileSpreadsheet :size="24" /></div>
              <strong>{{ selectedFile.name }}</strong>
              <span>{{ formatFileSize(selectedFile.size) }} · Ready to import</span>
            </template>
            <template v-else>
              <div class="csv-file-icon"><Upload :size="24" /></div>
              <strong>Drop your CSV file here</strong>
              <span>or choose a file from your device</span>
              <small>CSV format · Up to 100 MB</small>
            </template>
          </div>

          <p v-if="fileError" class="import-message error" role="alert">{{ fileError }}</p>
          <p v-if="importError" class="import-message error" role="alert">{{ importError }}</p>

          <div class="import-actions">
            <button
              v-if="selectedFile"
              class="secondary-ghost"
              type="button"
              :disabled="isImporting"
              @click="clearFile"
            >
              Remove file
            </button>
            <button
              class="import-submit"
              type="button"
              :disabled="!selectedFile || isImporting"
              @click="submitImport"
            >
              <LoaderCircle v-if="isImporting" class="spin" :size="17" />
              <Import v-else :size="17" />
              <span>{{ isImporting ? 'Importing activities…' : 'Import activities' }}</span>
            </button>
          </div>
        </section>

        <section v-if="importReport" class="import-results" aria-live="polite">
          <div class="results-heading">
            <div>
              <span class="eyebrow">Import complete</span>
              <h2>Results</h2>
            </div>
            <button class="text-action" type="button" @click="resetImport">Import another file</button>
          </div>

          <div class="result-counts">
            <div class="result-count imported-count">
              <strong>{{ importReport.imported }}</strong>
              <span>Imported</span>
            </div>
            <div class="result-count rejected-count">
              <strong>{{ importReport.rejected }}</strong>
              <span>Needs attention</span>
            </div>
          </div>

          <div v-if="importReport.rejections?.length" class="rejection-list">
            <div class="rejection-heading">
              <h3>Rows to review</h3>
              <button class="text-action" type="button" @click="downloadRejectedRows">Download rejection report</button>
            </div>
            <div
              v-for="rejection in importReport.rejections"
              :key="`${rejection.row}-${rejection.reasons.join('-')}`"
              class="rejection-row"
            >
              <strong>Row {{ rejection.row }}</strong>
              <span>{{ rejection.reasons.join(', ') }}</span>
            </div>
          </div>
          <p v-else class="all-imported">All rows were imported successfully.</p>
        </section>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { CircleCheckBig, Download, FileSpreadsheet, House, Import, LoaderCircle, Mail, Settings, Upload } from '@lucide/vue';
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useActivitiesStore } from '../stores/activities';
import { useAuthStore } from '../stores/auth';

interface ImportRejection {
  row: number;
  reasons: string[];
}

interface ImportReport {
  imported: number;
  rejected: number;
  rejections: ImportRejection[];
}

interface ApiFailure {
  response?: {
    data?: {
      message?: string;
    };
  };
}

const maximumFileSize = 100240 * 1000;
const templateCsv = 'title,description,priority,activity_status,due_at\nPlan the week,Choose three priorities,medium,pending,2026-10-01 09:00:00\n';
const router = useRouter();
const authStore = useAuthStore();
const store = useActivitiesStore();
const fileInput = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);
const importReport = ref<ImportReport | null>(null);
const fileError = ref('');
const importError = ref('');
const isDragging = ref(false);
const isImporting = ref(false);
const displayName = computed(() => authStore.user?.name || authStore.user?.user_name || 'Your account');
const userInitial = computed(() => displayName.value.charAt(0).toUpperCase());

function go(path: string): void {
  router.push(path);
}

function openFilePicker(): void {
  if (!isImporting.value) {
fileInput.value?.click();
}
}

function handleFileChange(event: Event): void {
  const input = event.target as HTMLInputElement;
  setSelectedFile(input.files?.[0] ?? null);
  input.value = '';
}

function handleDrop(event: DragEvent): void {
  isDragging.value = false;
  setSelectedFile(event.dataTransfer?.files?.[0] ?? null);
}

function setSelectedFile(file: File | null): void {
  fileError.value = '';
  importError.value = '';
  importReport.value = null;

  if (!file) {
    selectedFile.value = null;

    return;
  }

  if (!file.name.toLowerCase().endsWith('.csv')) {
    selectedFile.value = null;
    fileError.value = 'Choose a file with the .csv extension.';

    return;
  }

  if (file.size > maximumFileSize) {
    selectedFile.value = null;
    fileError.value = 'This file is larger than the 100 MB upload limit.';

    return;
  }

  selectedFile.value = file;
}

function clearFile(): void {
  if (!isImporting.value) {
selectedFile.value = null;
}
}

async function submitImport(): Promise<void> {
  if (!selectedFile.value || isImporting.value) {
return;
}

  isImporting.value = true;
  importError.value = '';
  importReport.value = null;

  try {
    importReport.value = await store.importCsv(selectedFile.value);
  } catch (error) {
    const failure = error as ApiFailure;
    importError.value = failure.response?.data?.message || 'The CSV could not be imported. Check the file and try again.';
  } finally {
    isImporting.value = false;
  }
}

function resetImport(): void {
  selectedFile.value = null;
  importReport.value = null;
  importError.value = '';
  fileError.value = '';
}

function downloadTemplate(): void {
  const templateUrl = URL.createObjectURL(new Blob([templateCsv], { type: 'text/csv;charset=utf-8' }));
  const link = document.createElement('a');
  link.href = templateUrl;
  link.download = 'steady-activities-template.csv';
  link.click();
  window.setTimeout(() => URL.revokeObjectURL(templateUrl), 1000);
}

function downloadRejectedRows(): void {
  if (!importReport.value?.rejections.length) {
return;
}

  const lines = [
    'row,reasons',
    ...importReport.value.rejections.map((rejection) =>
      `${rejection.row},"${rejection.reasons.join('; ').replaceAll('"', '""')}"`,
    ),
  ];
  const rejectedUrl = URL.createObjectURL(new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8' }));
  const link = document.createElement('a');
  link.href = rejectedUrl;
  link.download = 'steady-activities-rejected-rows.csv';
  link.click();
  window.setTimeout(() => URL.revokeObjectURL(rejectedUrl), 1000);
}

function formatFileSize(bytes: number): string {
  if (bytes < 1024) {
return `${bytes} B`;
}

  if (bytes < 1024 * 1024) {
return `${(bytes / 1024).toFixed(1)} KB`;
}

  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}
</script>
