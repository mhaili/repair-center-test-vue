<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1>Ordres de réparation</h1>
      <button class="btn btn-primary" @click="showCreate = true">+ Nouvel ordre</button>
    </div>

    <div class="row mb-3">
      <div class="col-md-4">
        <select class="form-select" v-model="filterStatus">
          <option value="">Tous les statuts</option>
          <option v-for="opt in STATUS_OPTIONS" :key="opt.value" :value="opt.value">
            {{ opt.label }}
          </option>
        </select>
      </div>
      <div class="col-md-4">
        <input
          type="text"
          class="form-control"
          placeholder="Rechercher par référence ou client..."
          v-model="searchQuery"
        />
      </div>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border" role="status"></div>
    </div>

    <div v-else-if="error" class="alert alert-danger">{{ error }}</div>

    <div v-else>
      <div v-if="filteredOrders.length === 0" class="alert alert-info">
        Aucun ordre de réparation trouvé.
      </div>
      <table v-else class="table table-hover align-middle">
        <thead>
          <tr>
            <th>Référence</th>
            <th>Client</th>
            <th>Statut</th>
            <th>Devis TTC</th>
            <th>Date</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="order in filteredOrders" :key="order.id" class="ro-row" @click="openOrder(order.id)">
            <td>{{ order.reference }}</td>
            <td>{{ order.customer?.name ?? '—' }}</td>
            <td>
              <span :class="'status-badge status-' + order.status">{{ statusLabel(order.status) }}</span>
            </td>
            <td>{{ order.quoteTotalTtc !== null ? new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(order.quoteTotalTtc) : '—' }}</td>
            <td>{{ order.createdAt }}</td>
            <td class="text-end" @click.stop>
              <button
                class="btn btn-sm btn-outline-success me-1"
                @click="statusOrder = order"
                :disabled="order.status === 'CANCELLED' || order.status === 'DELIVERED'"
              >
                Statut
              </button>
              <button
                class="btn btn-sm btn-outline-danger"
                @click="deleteOrder(order.id)"
                :disabled="order.status === 'DELIVERED'"
              >
                Supprimer
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <CreateRepairOrderModal v-if="showCreate" @close="showCreate = false" @created="onCreated" />
    <StatusModal
      v-if="statusOrder"
      :order="statusOrder"
      @close="statusOrder = null"
      @updated="onStatusUpdated"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import CreateRepairOrderModal from './CreateRepairOrderModal.vue'
import StatusModal from './StatusModal.vue'
import { STATUS_OPTIONS, statusLabel } from './status'

interface Customer {
  id: number
  name: string
  email: string | null
  phone?: string | null
}

interface RepairOrder {
  id: number
  reference: string
  status: string
  createdAt: string
  description: string | null
  quoteTotalTtc: number | null
  customer: Customer | null
}

const router = useRouter()

const repairOrders = ref<RepairOrder[]>([])
const loading = ref(false)
const error = ref<string | null>(null)
const filterStatus = ref('')
const searchQuery = ref('')
const showCreate = ref(false)
const statusOrder = ref<RepairOrder | null>(null)

const filteredOrders = computed(() =>
  repairOrders.value.filter((o) => {
    if (filterStatus.value && o.status !== filterStatus.value) return false
    if (searchQuery.value) {
      const q = searchQuery.value.toLowerCase()
      const match =
        o.reference.toLowerCase().includes(q) ||
        (o.customer?.name.toLowerCase().includes(q) ?? false)
      if (!match) return false
    }
    return true
  }),
)

function openOrder(id: number): void {
  router.push({ name: 'repair-order', params: { id: String(id) } })
}

async function loadRepairOrders(): Promise<void> {
  loading.value = true
  error.value = null
  try {
    const { data } = await axios.get<RepairOrder[]>('/api/repair-orders')
    repairOrders.value = data
  } catch {
    error.value = 'Erreur lors du chargement des ordres de réparation'
  } finally {
    loading.value = false
  }
}

async function deleteOrder(id: number): Promise<void> {
  if (!confirm('Supprimer cet ordre de réparation ?')) return
  try {
    await axios.delete(`/api/repair-orders/${id}`)
    await loadRepairOrders()
  } catch (e: any) {
    alert(e.response?.data?.error ?? 'Erreur lors de la suppression')
  }
}

function onCreated(): void {
  showCreate.value = false
  loadRepairOrders()
}

function onStatusUpdated(): void {
  statusOrder.value = null
  loadRepairOrders()
}

onMounted(loadRepairOrders)
</script>
