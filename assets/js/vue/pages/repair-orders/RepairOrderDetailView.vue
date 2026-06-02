<template>
  <div>
    <router-link to="/" class="btn btn-link px-0 mb-3 text-decoration-none">
      ← Retour à la liste
    </router-link>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border" role="status"></div>
    </div>

    <div v-else-if="error" class="alert alert-danger">{{ error }}</div>

    <div v-else-if="order">
      <!-- En-tête OR -->
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">{{ order.reference }}</h5>
          <span :class="'status-badge status-' + order.status">{{ statusLabel(order.status) }}</span>
        </div>
        <div class="card-body">
          <div class="row mb-2">
            <div class="col-md-6"><strong>Client :</strong> {{ order.customer?.name ?? '—' }}</div>
            <div class="col-md-6"><strong>Créé le :</strong> {{ order.createdAt }}</div>
          </div>
          <p class="mb-0"><strong>Description :</strong> {{ order.description ?? '—' }}</p>
        </div>
      </div>

      <!-- Devis -->
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0">Devis</h6>
          <button v-if="!quote && !orderClosed" class="btn btn-sm btn-primary" @click="createQuote" :disabled="quoteLoading">
            Créer le devis
          </button>
        </div>

        <div class="card-body">
          <div v-if="quoteLoading" class="text-center py-3">
            <div class="spinner-border spinner-border-sm" role="status"></div>
          </div>

          <div v-else-if="!quote" class="text-muted">
            Aucun devis pour cet ordre.
          </div>

          <div v-else>
            <!-- Tableau des lignes -->
            <table class="table table-sm mb-4">
              <thead class="table-light">
                <tr>
                  <th>Type</th>
                  <th>Désignation</th>
                  <th class="text-end">Qté / H</th>
                  <th class="text-end">PU HT</th>
                  <th class="text-end">Remise</th>
                  <th class="text-end">HT</th>
                  <th class="text-end">TVA</th>
                  <th class="text-end">TTC</th>
                  <th v-if="!orderClosed"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="quote.lines.length === 0">
                  <td colspan="9" class="text-muted text-center py-3">Aucune ligne — ajoutez une pièce ou de la main d'œuvre.</td>
                </tr>
                <tr v-for="line in quote.lines" :key="line.id">
                  <td>
                    <span class="badge" :class="line.type === 'PART' ? 'bg-info' : 'bg-warning text-dark'">
                      {{ line.type === 'PART' ? 'Pièce' : 'MO' }}
                    </span>
                  </td>
                  <td>
                    <span v-if="line.type === 'PART'">{{ line.part.label }} <small class="text-muted">({{ line.part.reference }})</small></span>
                    <span v-else>{{ laborTypeLabel(line.laborType) }}</span>
                  </td>
                  <td class="text-end">{{ line.type === 'PART' ? line.quantity : line.hours + ' h' }}</td>
                  <td class="text-end">{{ line.type === 'PART' ? fmt(line.unitPrice) : fmt(line.hourlyRate) + '/h' }}</td>
                  <td class="text-end">{{ line.discount > 0 ? line.discount + ' %' : '—' }}</td>
                  <td class="text-end">{{ fmt(line.amountHt) }}</td>
                  <td class="text-end">{{ fmt(line.vatAmount) }}</td>
                  <td class="text-end">{{ fmt(line.amountTtc) }}</td>
                  <td v-if="!orderClosed" class="text-end">
                    <button class="btn btn-sm btn-outline-danger py-0" @click="removeLine(line.id)">✕</button>
                  </td>
                </tr>
              </tbody>
            </table>

            <!-- Totaux -->
            <div class="row justify-content-end mb-4">
              <div class="col-md-4">
                <table class="table table-sm table-borderless mb-0">
                  <tbody>
                    <tr>
                      <td class="text-muted">Total HT</td>
                      <td class="text-end fw-semibold">{{ fmt(quote.totalHt) }}</td>
                    </tr>
                    <tr v-if="quote.totalDiscount > 0">
                      <td class="text-muted">Remise</td>
                      <td class="text-end text-danger">− {{ fmt(quote.totalDiscount) }}</td>
                    </tr>
                    <tr>
                      <td class="text-muted">TVA (20 %)</td>
                      <td class="text-end">{{ fmt(quote.totalVat) }}</td>
                    </tr>
                    <tr class="border-top">
                      <td class="fw-bold">Total TTC</td>
                      <td class="text-end fw-bold fs-5">{{ fmt(quote.totalTtc) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Formulaire ajout de ligne -->
            <div v-if="!orderClosed">
              <h6 class="mb-3">Ajouter une ligne</h6>
              <div class="d-flex gap-2 mb-3">
                <button class="btn btn-sm" :class="addForm.type === 'PART' ? 'btn-primary' : 'btn-outline-primary'" @click="addForm.type = 'PART'">Pièce</button>
                <button class="btn btn-sm" :class="addForm.type === 'LABOR' ? 'btn-warning' : 'btn-outline-warning'" @click="addForm.type = 'LABOR'">Main d'œuvre</button>
              </div>

              <!-- Formulaire pièce -->
              <div v-if="addForm.type === 'PART'" class="row g-2 align-items-end">
                <div class="col-md-5">
                  <label class="form-label form-label-sm">Pièce</label>
                  <select v-model="addForm.partId" class="form-select form-select-sm">
                    <option value="">— Choisir —</option>
                    <option v-for="p in parts" :key="p.id" :value="p.id">{{ p.label }} ({{ p.reference }}) — {{ fmt(p.salePrice) }}</option>
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label form-label-sm">Quantité</label>
                  <input v-model.number="addForm.quantity" type="number" min="1" class="form-control form-control-sm" />
                </div>
                <div class="col-md-2">
                  <label class="form-label form-label-sm">Remise %</label>
                  <input v-model.number="addForm.discount" type="number" min="0" max="100" step="0.5" class="form-control form-control-sm" />
                </div>
                <div class="col-md-2">
                  <button class="btn btn-sm btn-success w-100" @click="addLine" :disabled="!addForm.partId || addForm.quantity < 1">Ajouter</button>
                </div>
              </div>

              <!-- Formulaire MO -->
              <div v-if="addForm.type === 'LABOR'" class="row g-2 align-items-end">
                <div class="col-md-3">
                  <label class="form-label form-label-sm">Type de travail</label>
                  <select v-model="addForm.laborType" class="form-select form-select-sm">
                    <option value="">— Choisir —</option>
                    <option value="TOLERIE">Tôlerie (65 €/h)</option>
                    <option value="PEINTURE">Peinture (70 €/h)</option>
                    <option value="MECANIQUE">Mécanique (60 €/h)</option>
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label form-label-sm">Heures</label>
                  <input v-model.number="addForm.hours" type="number" min="0.5" step="0.5" class="form-control form-control-sm" />
                </div>
                <div class="col-md-2">
                  <label class="form-label form-label-sm">Remise %</label>
                  <input v-model.number="addForm.discount" type="number" min="0" max="100" step="0.5" class="form-control form-control-sm" />
                </div>
                <div class="col-md-2">
                  <button class="btn btn-sm btn-success w-100" @click="addLine" :disabled="!addForm.laborType || addForm.hours <= 0">Ajouter</button>
                </div>
              </div>

              <div v-if="addError" class="alert alert-danger mt-2 py-2">{{ addError }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { statusLabel } from './status'

const props = defineProps<{ id: string }>()

interface Customer {
  name: string
  email: string | null
  phone?: string | null
}

interface RepairOrder {
  id: number
  reference: string
  status: string
  description: string | null
  createdAt: string
  customer: Customer | null
}

interface QuoteLine {
  id: number
  type: 'PART' | 'LABOR'
  discount: number
  grossAmountHt: number
  amountHt: number
  vatAmount: number
  amountTtc: number
  // PART
  part?: { id: number; reference: string; label: string }
  quantity?: number
  unitPrice?: number
  // LABOR
  laborType?: string
  hours?: number
  hourlyRate?: number
}

interface Quote {
  id: number
  createdAt: string
  lines: QuoteLine[]
  totalHt: number
  totalDiscount: number
  totalVat: number
  totalTtc: number
}

interface Part {
  id: number
  reference: string
  label: string
  salePrice: number
}

const order      = ref<RepairOrder | null>(null)
const quote      = ref<Quote | null>(null)
const parts      = ref<Part[]>([])
const loading    = ref(false)
const quoteLoading = ref(false)
const error      = ref<string | null>(null)
const addError   = ref<string | null>(null)

const addForm = ref({ type: 'PART', partId: '', quantity: 1, laborType: '', hours: 1, discount: 0 })

const orderClosed = computed(() =>
  order.value?.status === 'DELIVERED' || order.value?.status === 'CANCELLED'
)

const fmt = (amount: number): string =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount)

const laborTypeLabel = (type: string | undefined): string =>
  ({ TOLERIE: 'Tôlerie', PEINTURE: 'Peinture', MECANIQUE: 'Mécanique' }[type ?? ''] ?? type ?? '')

async function load(): Promise<void> {
  loading.value = true
  error.value = null
  try {
    const [orderRes, partsRes] = await Promise.all([
      axios.get<RepairOrder>(`/api/repair-orders/${props.id}`),
      axios.get<Part[]>('/api/parts'),
    ])
    order.value = orderRes.data
    parts.value = partsRes.data
    await loadQuote()
  } catch {
    error.value = 'Ordre de réparation introuvable'
  } finally {
    loading.value = false
  }
}

async function loadQuote(): Promise<void> {
  try {
    const res = await axios.get<Quote>(`/api/repair-orders/${props.id}/quote`)
    quote.value = res.status === 204 ? null : res.data
  } catch {
    quote.value = null
  }
}

async function createQuote(): Promise<void> {
  quoteLoading.value = true
  try {
    const res = await axios.post<Quote>(`/api/repair-orders/${props.id}/quote`)
    quote.value = res.data
  } finally {
    quoteLoading.value = false
  }
}

async function addLine(): Promise<void> {
  addError.value = null
  const payload: Record<string, unknown> = {
    type:     addForm.value.type,
    discount: addForm.value.discount,
  }
  if (addForm.value.type === 'PART') {
    payload.partId   = addForm.value.partId
    payload.quantity = addForm.value.quantity
  } else {
    payload.laborType = addForm.value.laborType
    payload.hours     = addForm.value.hours
  }
  try {
    await axios.post(`/api/repair-orders/${props.id}/quote/lines`, payload)
    addForm.value = { type: addForm.value.type, partId: '', quantity: 1, laborType: '', hours: 1, discount: 0 }
    await loadQuote()
  } catch (e: unknown) {
    if (axios.isAxiosError(e)) {
      addError.value = e.response?.data?.error ?? 'Erreur lors de l\'ajout'
    }
  }
}

async function removeLine(lineId: number): Promise<void> {
  await axios.delete(`/api/repair-orders/${props.id}/quote/lines/${lineId}`)
  await loadQuote()
}

onMounted(load)
</script>
