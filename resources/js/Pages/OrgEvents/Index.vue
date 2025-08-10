<script setup lang="ts">
import {computed, ref} from 'vue'
import {usePage} from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

/** FullCalendar */
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import rrulePlugin from '@fullcalendar/rrule'

/** PrimeVue */
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import Card from 'primevue/card'
import Divider from 'primevue/divider'
import {useDark} from "@vueuse/core";
import {route} from "ziggy-js";

type OrgEventType = {
    id: number
    name: string
    category?: string | null
    color?: string | null // hex or css color
}

type OrgEventDto = {
    id: number
    title: string
    description?: string | null
    start: string // ISO
    end?: string | null // ISO
    all_day?: boolean
    repeats?: boolean
    rrule?: string | null
    location?: string | null
    type: OrgEventType | null
    masons_only: boolean
    degree_required: 'none' | 'entered apprentice' | 'fellowcraft' | 'master mason'
    open_to: 'all' | 'members' | 'officers'
    is_public: boolean
    can_edit?: boolean
}

type Role = {
    id: number
    guard_name: string
    name: string
    created_at: string
    updated_at: string
}

const $page = usePage()
const isDark = useDark()

const props = defineProps<{
    events: OrgEventDto[]
    types: OrgEventType[]              // for legend
    currentMonth?: string | null
}>()

/** ---- Roles / ability helpers ---- */
const user = computed(() => ($page.props as any)?.auth?.user ?? null)
const userRoles = computed<Role[]>(() => (user.value?.roles ?? []) as Role[])

const hasRole = (role: string) => userRoles.value?.some(r => r.name.toLowerCase() === role.toLowerCase())
const isOfficer = computed(() => hasRole('Officer'))
const isAdmin = computed(() => hasRole('Administrator') || hasRole('Admin'))
const isSecretary = computed(() => hasRole('Secretary'))
const canManage = computed(() => isOfficer.value || isAdmin.value || isSecretary.value)

/** ---- Visibility: who can see details? ----
 * Calendar blocks are always visible; dialog details are gated.
 */
function canViewDetails(event: OrgEventDto): boolean {
    // Officers/Admin/Secretary can always view
    if (canManage.value) return true

    // Not signed in: must not be masons_only AND open_to must be 'all'
    if (!user.value) {
        return !event.masons_only && event.open_to === 'all'
    }

    // Signed-in users
    const isMason = hasRole('Mason') || hasRole('Entered Apprentice') || hasRole('Fellowcraft') || hasRole('Master Mason')
    const isMember = hasRole('Member')
    const isOfficerRole = hasRole('Officer')

    // open_to gate
    if (event.open_to === 'officers' && !(isOfficerRole || isAdmin.value || isSecretary.value)) return false
    if (event.open_to === 'members' && !(isMember || isOfficerRole || isAdmin.value || isSecretary.value)) return false

    // masons_only gate: non-masons can’t see details
    if (event.masons_only && !isMason) return false

    // degree gate
    const isEA = hasRole('Entered Apprentice')
    const isFC = hasRole('Fellowcraft')
    const isMM = hasRole('Master Mason')

    switch (event.degree_required.toLowerCase()) {
        case 'entered apprentice':
            if (!(isEA || isFC || isMM)) return false
            break
        case 'fellowcraft':
            if (!(isFC || isMM)) return false
            break
        case 'master mason':
            if (!isMM) return false
            break
        case 'none':
        default:
            // ok
            break
    }

    return true
}

function toDuration(startIso?: string | null, endIso?: string | null) {
    if (!startIso || !endIso) return undefined
    const start = new Date(startIso).getTime()
    const end = new Date(endIso).getTime()
    const ms = Math.max(0, end - start)
    const totalMinutes = Math.round(ms / 60000)
    const hours = Math.floor(totalMinutes / 60)
    const minutes = totalMinutes % 60
    return { hours, minutes }
}

const calendarEvents = computed(() =>
    props.events
        .filter(ev => ev.is_public || ev.can_edit || canManage.value)
        .map(ev => {
            const base = {
                id: String(ev.id),
                title: ev.title,
                allDay: !!ev.all_day,
                backgroundColor: ev.type?.color || 'var(--p-primary-color, #5868fc)',
                borderColor: ev.type?.color || 'var(--p-primary-color, #5868fc)',
                textColor: '#fff',
                classNames: !ev.is_public ? ['fc-draft'] : [],
                extendedProps: ev
            }

            if (ev.rrule) {
                // recurring event
                const startTime = ev.all_day ? undefined : ev.start?.substring(11, 16) // "HH:mm"
                const duration = ev.all_day ? undefined : toDuration(ev.start, ev.end)
                return {
                    ...base,
                    rrule: ev.rrule,
                    startTime,
                    duration
                }
            } else {
                // single instance
                return {
                    ...base,
                    start: ev.start,
                    end: ev.end ?? undefined
                }
            }
        })
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
    if (url) {
        window.location.href = `${url}?start=${encodeURIComponent(dateInfo.dateStr)}`
    }
}

function onCreateBlank() {
    if (!canManage.value) return
    const url = route('events.create') // adjust
    if (url) window.location.href = url
}

function onEditSelected() {
    if (!canManage.value || !selected.value) return
    const url = route('events.edit', selected.value.id) // adjust
    if (url) window.location.href = url
}

/** ---- FullCalendar options ---- */
const todayISO = new Date().toISOString().slice(0, 10)
const initialDate = computed(() => props.currentMonth ?? todayISO)

const headerToolbar = {
    left: 'prev,next today',
    center: 'title',
    right: 'dayGridMonth'
}

function eventDidMount(info: any) {
    // Add a small lock badge for restricted-detail events
    const event: OrgEventDto = info.event.extendedProps
    const allowed = canViewDetails(event)
    if (!allowed) {
        const lock = document.createElement('span')
        lock.className = 'pi pi-lock fc-lock-icon'
        lock.setAttribute('aria-label', 'Restricted details')
        // tiny inline badge
        Object.assign(lock.style, {
            fontSize: '0.75rem',
            marginLeft: '0.375rem',
            verticalAlign: 'middle'
        })
        info.el.querySelector('.fc-event-title')?.append(lock)
        // subdue the event background slightly to hint it’s restricted (but still visible)
        info.el.style.filter = 'saturate(0.7) brightness(0.95)'
    }
}

const calendarOptions = computed(() => ({
    plugins: [dayGridPlugin, interactionPlugin, rrulePlugin],
    initialView: 'dayGridMonth',
    initialDate: initialDate.value,
    headerToolbar,
    events: calendarEvents.value,   // your computed from earlier
    dayMaxEvents: true,
    fixedWeekCount: true,
    showNonCurrentDates: true,
    firstDay: 0,
    height: 'auto',
    eventClick: onEventClick,
    dateClick: onDateClick,
    eventDidMount
}))

/** Legend chips for types */
const legendTypes = computed(() => props.types || [])
</script>

<template>
    <AppLayout title="Events">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Events</h2>
                <div class="hidden sm:flex gap-2">
                    <Button
                        v-if="canManage"
                        icon="pi pi-plus"
                        label="New Event"
                        @click="onCreateBlank"
                    />
                </div>
            </div>
        </template>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-4">
            <!-- Type legend -->
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
          <i class="pi pi-lock"/> Restricted details
        </span>
            </div>

            <!-- Calendar -->
            <Card class="bg-white dark:bg-surface-800 shadow">
                <template #content>
                    <FullCalendar :options="calendarOptions" />
                </template>
            </Card>

            <!-- Mobile create -->
            <div class="sm:hidden">
                <Button v-if="canManage" icon="pi pi-plus" label="New Event" class="w-full" @click="onCreateBlank"/>
            </div>
        </div>

        <!-- Details Dialog -->
        <Dialog
            v-model:visible="showDialog"
            :header="selected ? selected.title : 'Event'"
            modal
            class="w-full sm:w-[36rem]"
        >
            <div v-if="selected">
                <!-- top chips -->
                <div class="flex flex-wrap items-center gap-2 mb-3">
                    <Tag
                        v-if="selected.type && selected.type.name"
                        :value="selected.type.name"
                        :style="{ backgroundColor: (selected.type && selected.type.color) || 'var(--p-primary-color)', color: '#fff', borderColor: 'transparent' }"
                        rounded
                    />
                    <Tag v-if="selected && !selected.is_public" value="Draft" severity="warning" />
                    <Badge v-if="selected.all_day" value="All day" />
                    <Badge v-if="selected.masons_only" value="Masons only" severity="danger" />
                    <Badge v-else value="Public" severity="success" />
                    <Badge v-if="selected.open_to === 'members'" value="Members" severity="info" />
                    <Badge v-if="selected.open_to === 'officers'" value="Officers" severity="warning" />
                </div>

                <!-- times -->
                <div class="text-sm text-gray-700 dark:text-gray-200">
                    <div class="mb-1">
                        <i class="pi pi-calendar mr-2" />
                        <span>
          {{ new Date(selected.start).toLocaleString() }}
          <template v-if="selected.end"> – {{ new Date(selected.end).toLocaleString() }}</template>
        </span>
                    </div>
                    <div v-if="selected.location" class="mb-2">
                        <i class="pi pi-map-marker mr-2" />
                        <span>{{ selected.location }}</span>
                    </div>
                </div>

                <Divider class="my-3" />

                <!-- description / restrictions -->
                <div v-if="canSeeSelected" class="prose dark:prose-invert max-w-none text-sm">
                    <div v-html="selected.description || '<em>No description provided.</em>'"></div>
                </div>
                <div v-else class="text-sm text-amber-700 dark:text-amber-300">
                    <i class="pi pi-lock mr-2" />
                    You don’t have permission to view the details of this event.
                </div>
            </div>

            <!-- footer MUST be a direct child slot -->
            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button
                        v-if="canManage && selected"
                        icon="pi pi-pencil"
                        label="Edit"
                        @click="onEditSelected"
                    />
                    <Button
                        icon="pi pi-times"
                        label="Close"
                        severity="secondary"
                        text
                        @click="showDialog = false"
                    />
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
/* Make restricted lock icon sit nicely inline with title */
:deep(.fc-lock-icon) {
    opacity: .9
}

/* Day grid minor polish for dark */
:deep(.fc) {
    --fc-page-bg-color: transparent;
}

:deep(.fc .fc-daygrid-day) {
    border-color: rgba(0, 0, 0, .08);
}

:deep(.app-dark .fc .fc-daygrid-day) {
    border-color: rgba(255, 255, 255, .08);
}

/* Draft event stripe overlay + dashed border */
:deep(.fc-draft .fc-event-main) {
    position: relative;
    /* overlay subtle white stripes on top of the event color */
    background-image: repeating-linear-gradient(
        45deg,
        rgba(255,255,255,0.18) 0,
        rgba(255,255,255,0.18) 6px,
        rgba(255,255,255,0) 6px,
        rgba(255,255,255,0) 12px
    ) !important;
}

:deep(.app-dark .fc-draft .fc-event-main) {
    /* slightly stronger in dark mode for visibility */
    background-image: repeating-linear-gradient(
        45deg,
        rgba(255,255,255,0.25) 0,
        rgba(255,255,255,0.25) 6px,
        rgba(255,255,255,0) 6px,
        rgba(255,255,255,0) 12px
    ) !important;
}

:deep(.fc-draft) {
    border-style: dashed !important;
    opacity: 0.95; /* tiny hint it's different without hiding it */
}
</style>
