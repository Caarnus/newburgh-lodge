<script setup lang="ts">
import { computed, ref } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

/** FullCalendar */
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'

/** PrimeVue */
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import Card from 'primevue/card'
import Divider from 'primevue/divider'
import { useDark } from '@vueuse/core'
import { route } from 'ziggy-js'

type OrgEventType = {
    id: number
    name: string
    category?: string | null
    color?: string | null
}

type OrgEventDto = {
    id: number
    title: string
    description?: string | null
    start: string // ISO
    end?: string | null // ISO
    all_day?: boolean
    location?: string | null
    type: OrgEventType | null

    masons_only?: boolean
    degree_required?: 'none' | 'entered apprentice' | 'fellowcraft' | 'master mason'
    open_to?: 'all' | 'members' | 'officers'
    is_public?: boolean
    can_edit?: boolean

    parent_id?: number | null
    occurrence_id?: string | null
    timezone?: string | null
}

type Role = {
    id: number
    name: string
}

const $page = usePage()
useDark()

const props = defineProps<{
    events: OrgEventDto[]
    types: OrgEventType[]
    currentMonth?: string | null
}>()

/** ---- Roles / ability helpers ---- */
const user = computed(() => ($page.props as any)?.auth?.user ?? null)
const userRoles = computed<Role[]>(() => (user.value?.roles ?? []) as Role[])

const hasRole = (role: string) => userRoles.value?.some((r) => (r.name ?? '').toLowerCase() === role.toLowerCase())
const isOfficer = computed(() => hasRole('Officer'))
const isAdmin = computed(() => hasRole('Administrator') || hasRole('Admin'))
const isSecretary = computed(() => hasRole('Secretary'))
const canManage = computed(() => isOfficer.value || isAdmin.value || isSecretary.value)

/** ---- Visibility: who can see details? ---- */
function canViewDetails(event: OrgEventDto): boolean {
    if (canManage.value) return true

    const openTo = (event.open_to ?? 'all') as any
    const degree = (event.degree_required ?? 'none') as any
    const masonsOnly = !!event.masons_only

    if (!user.value) {
        return !masonsOnly && openTo === 'all'
    }

    const isMason = hasRole('Mason') || hasRole('Entered Apprentice') || hasRole('Fellowcraft') || hasRole('Master Mason')
    const isMember = hasRole('Member')
    const isOfficerRole = hasRole('Officer')

    if (openTo === 'officers' && !(isOfficerRole || isAdmin.value || isSecretary.value)) return false
    if (openTo === 'members' && !(isMember || isOfficerRole || isAdmin.value || isSecretary.value)) return false

    if (masonsOnly && !isMason) return false

    const isEA = hasRole('Entered Apprentice')
    const isFC = hasRole('Fellowcraft')
    const isMM = hasRole('Master Mason')

    switch (String(degree).toLowerCase()) {
        case 'entered apprentice':
            if (!(isEA || isFC || isMM)) return false
            break
        case 'fellowcraft':
            if (!(isFC || isMM)) return false
            break
        case 'master mason':
            if (!isMM) return false
            break
        default:
            break
    }

    return true
}

const calendarEvents = computed(() =>
    (props.events ?? [])
        .filter((event) => !!event && ((event.is_public ?? true) || !!event.can_edit || canManage.value))
        .map((event) => ({
            id: event.occurrence_id ? String(event.occurrence_id) : String(event.id),
            title: event.title,
            allDay: !!event.all_day,
            classNames: !(event.is_public ?? true) ? ['fc-draft'] : [],
            start: event.start,
            end: event.end ?? undefined,
            extendedProps: event,
        }))
)

/** ---- Dialog handling ---- */
const showDialog = ref(false)
const selected = ref<OrgEventDto | null>(null)
const canSeeSelected = computed(() => (selected.value ? canViewDetails(selected.value) : false))

function onEventClick(arg: any) {
    selected.value = arg.event.extendedProps as OrgEventDto
    showDialog.value = true
}

function onDateClick(dateInfo: { dateStr: string }) {
    if (!canManage.value) return
    const url = route('events.create')
    if (url) window.location.href = `${url}?start=${encodeURIComponent(dateInfo.dateStr)}`
}

function onCreateBlank() {
    if (!canManage.value) return
    const url = route('events.create')
    if (url) window.location.href = url
}

function onEditSelected() {
    if (!canManage.value || !selected.value) return
    const id = (selected.value as any).parent_id ?? selected.value.id
    const url = route('events.edit', id)
    if (url) window.location.href = url
}

const deleteForm = useForm({})
function onDeleteSelected() {
    if (!canManage.value || !selected.value) return
    const id = (selected.value as any).parent_id ?? selected.value.id
    if (!confirm('Delete this event?')) return
    deleteForm.delete(route('events.destroy', id), {
        preserveScroll: true,
        onSuccess: () => {
            showDialog.value = false
            selected.value = null
        },
    })
}

/** ---- FullCalendar options ---- */
const todayISO = new Date().toISOString().slice(0, 10)
const initialDate = computed(() => props.currentMonth ?? todayISO)

const headerToolbar = {
    left: 'prev,next today',
    center: 'title',
    right: 'dayGridMonth',
}

function isOverflowing(el?: Element | null) {
    if (!el) return false
    const e = el as HTMLElement
    return e.scrollWidth > e.clientWidth
}

function eventDidMount(info: any) {
    const event = info.event.extendedProps as OrgEventDto
    const main = (info.el.querySelector('.fc-event-main') as HTMLElement) || (info.el as HTMLElement)

    const bg = event.type?.color || 'var(--p-primary-color, #5868fc)'
    main.style.backgroundColor = bg
    main.style.borderColor = bg
    main.style.color = '#fff'

    const titleEl = info.el.querySelector('.fc-event-title')
    if (isOverflowing(titleEl)) {
        info.el.setAttribute('title', event.title)
    }

    if (!(event.is_public ?? true)) {
        info.el.classList.add('fc-draft')
        const badge = document.createElement('span')
        badge.className = 'fc-draft-badge'
        badge.textContent = 'Draft'
        main.appendChild(badge)
    }

    const allowed = canViewDetails(event)
    if (!allowed) {
        const lock = document.createElement('span')
        lock.className = 'pi pi-lock fc-lock-icon'
        Object.assign(lock.style, { fontSize: '0.75rem', marginLeft: '0.375rem', verticalAlign: 'middle' })
        info.el.querySelector('.fc-event-title')?.append(lock)
        info.el.style.filter = 'saturate(0.85) brightness(0.98)'
    }
}

const calendarOptions = computed(() => ({
    plugins: [dayGridPlugin, interactionPlugin],
    timeZone: 'local',
    initialView: 'dayGridMonth',
    initialDate: initialDate.value,
    headerToolbar,
    events: calendarEvents.value,
    dayMaxEvents: true,
    fixedWeekCount: true,
    showNonCurrentDates: true,
    firstDay: 0,
    aspectRatio: 1.1,
    eventClick: onEventClick,
    dateClick: onDateClick,
    eventDidMount,
}))

const legendTypes = computed(() => props.types || [])
</script>

<template>
    <AppLayout title="Events">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Events</h2>
                <div class="hidden sm:flex gap-2">
                    <Button v-if="canManage" icon="pi pi-plus" label="New Event" @click="onCreateBlank" />
                </div>
            </div>
        </template>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-4">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm text-gray-600 dark:text-gray-300 mr-2">Legend:</span>
                <Tag
                    v-for="t in legendTypes"
                    :key="t.id"
                    :value="t.name"
                    :style="{ backgroundColor: t.color || 'var(--p-primary-color)', color: '#fff', borderColor: 'transparent' }"
                    rounded
                />
                <span class="ml-auto flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
          <i class="pi pi-lock" /> Restricted details
        </span>
            </div>

            <Card class="bg-white dark:bg-surface-800 shadow">
                <template #content>
                    <FullCalendar :options="calendarOptions" />
                </template>
            </Card>

            <div class="sm:hidden">
                <Button v-if="canManage" icon="pi pi-plus" label="New Event" class="w-full" @click="onCreateBlank" />
            </div>
        </div>

        <Dialog v-model:visible="showDialog" :header="selected ? selected.title : 'Event'" modal class="w-full sm:w-[36rem]">
            <div v-if="selected">
                <div class="flex flex-wrap items-center gap-2 mb-3">
                    <Tag
                        v-if="selected.type && selected.type.name"
                        :value="selected.type.name"
                        :style="{ backgroundColor: selected.type.color || 'var(--p-primary-color)', color: '#fff', borderColor: 'transparent' }"
                        rounded
                    />
                    <Tag v-if="!(selected.is_public ?? true)" value="Draft" severity="warning" />
                    <Badge v-if="selected.all_day" value="All day" />
                    <Badge v-if="selected.masons_only" value="Masons only" severity="danger" />
                    <Badge v-else value="Public" severity="success" />
                    <Badge v-if="selected.open_to === 'members'" value="Members" severity="info" />
                    <Badge v-if="selected.open_to === 'officers'" value="Officers" severity="warning" />
                </div>

                <div class="text-sm text-gray-700 dark:text-gray-200">
                    <div class="mb-1">
                        <i class="pi pi-calendar mr-2" />
                        <span>
              {{ new Date(selected.start).toLocaleString('en-US', { month: 'numeric', year: 'numeric', day: 'numeric', hour: 'numeric', minute: 'numeric' }) }}
              <template v-if="selected.end">
                –
                {{ new Date(selected.end).toLocaleString('en-US', { month: 'numeric', year: 'numeric', day: 'numeric', hour: 'numeric', minute: 'numeric' }) }}
              </template>
            </span>
                    </div>
                    <div v-if="selected.location" class="mb-2">
                        <i class="pi pi-map-marker mr-2" />
                        <span>{{ selected.location }}</span>
                    </div>
                </div>

                <Divider class="my-3" />

                <div v-if="canSeeSelected" class="prose dark:prose-invert max-w-none text-sm">
                    <div v-html="selected.description || '<em>No description provided.</em>'"></div>
                </div>
                <div v-else class="text-sm text-amber-700 dark:text-amber-300">
                    <i class="pi pi-lock mr-2" />
                    You don’t have permission to view the details of this event.
                </div>
            </div>

            <template #footer>
                <div class="w-full flex justify-between gap-2">
                    <div class="flex">
                        <Button v-if="canManage && selected" icon="pi pi-delete" label="Delete" severity="danger" @click="onDeleteSelected" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <Button v-if="canManage && selected" icon="pi pi-pencil" label="Edit" @click="onEditSelected" />
                        <Button icon="pi pi-times" label="Close" severity="secondary" text @click="showDialog = false" />
                    </div>
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
:deep(.fc .fc-daygrid-event .fc-event-main) {
    background-color: inherit;
    border-color: inherit;
    color: inherit;
}

:deep(.fc-draft .fc-event-main) {
    position: relative;
    background-image: repeating-linear-gradient(
        45deg,
        rgba(255, 255, 255, 0.18) 0,
        rgba(255, 255, 255, 0.18) 6px,
        rgba(255, 255, 255, 0) 6px,
        rgba(255, 255, 255, 0) 12px
    ) !important;
    border-style: dashed !important;
}

:deep(.app-dark .fc-draft .fc-event-main) {
    background-image: repeating-linear-gradient(
        45deg,
        rgba(255, 255, 255, 0.25) 0,
        rgba(255, 255, 255, 0.25) 6px,
        rgba(255, 255, 255, 0) 6px,
        rgba(255, 255, 255, 0) 12px
    ) !important;
}

:deep(.fc-draft-badge) {
    position: absolute;
    top: 2px;
    right: 4px;
    font-size: 10px;
    line-height: 1;
    padding: 2px 6px;
    border-radius: 9999px;
    background: rgba(0, 0, 0, 0.35);
    color: #fff;
    pointer-events: none;
}

:deep(.app-dark .fc-draft-badge) {
    background: rgba(0, 0, 0, 0.5);
}

:deep(.fc-lock-icon) {
    opacity: 0.95;
}
</style>
