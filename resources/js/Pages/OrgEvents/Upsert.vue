<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

import Card from 'primevue/card'
import Button from 'primevue/button'
import Divider from 'primevue/divider'
import InputText from 'primevue/inputtext'
import ToggleSwitch from 'primevue/toggleswitch'
import Select from 'primevue/select'
import MultiSelect from 'primevue/multiselect'
import DatePicker from 'primevue/datepicker'
import InputNumber from 'primevue/inputnumber'
import Editor from 'primevue/editor'
import {route} from "ziggy-js";

type OrgEventType = {
    id: number
    name: string
    color?: string | null
}

type OrgEventDto = {
    id?: number
    title?: string
    description?: string | null
    location?: string | null
    all_day?: boolean
    start?: string | null // ISO
    end?: string | null   // ISO
    type_id?: number | null

    masons_only?: boolean
    open_to?: 'all' | 'members' | 'officers'
    degree_required?: 'none' | 'entered apprentice' | 'fellowcraft' | 'master mason'
    is_public?: boolean

    // recurrence: we store RRULE string in the DB (recommended)
    repeats?: boolean
    rrule?: string | null
    repeat_options?: {
        mode: 'none' | 'nth-weekday' | 'interval'
        // For nth-weekday, e.g. 3rd Tuesday
        nth?: number | null        // 1..5 or -1 for last
        weekday?: string | null    // 'MO','TU','WE','TH','FR','SA','SU'
        // For general interval rule
        freq?: 'DAILY' | 'WEEKLY' | 'MONTHLY' | 'YEARLY' | null
        interval?: number | null   // every X
        byweekday?: string[] | null
        // End options
        ends?: 'never' | 'until' | 'count'
        until?: string | null      // ISO date
        count?: number | null
    } | null
}

const $page = usePage()

const props = defineProps<{
    event?: OrgEventDto | null
    types: OrgEventType[]
    // optional: a preselected start date from the calendar query (?start=YYYY-MM-DD)
    preselectStart?: string | null
}>()

const isEdit = computed(() => !!props.event?.id)
const pageTitle = computed(() => (isEdit.value ? 'Edit Event' : 'Create Event'))

// helpers
const degreeOptions = [
    { label: 'None', value: 'none' },
    { label: 'Entered Apprentice', value: 'entered apprentice' },
    { label: 'Fellowcraft', value: 'fellowcraft' },
    { label: 'Master Mason', value: 'master mason' }
]

const openToOptions = [
    { label: 'All', value: 'all' },
    { label: 'Members', value: 'members' },
    { label: 'Officers', value: 'officers' }
]

const weekdayChoices = [
    { label: 'Sunday', value: 'SU' },
    { label: 'Monday', value: 'MO' },
    { label: 'Tuesday', value: 'TU' },
    { label: 'Wednesday', value: 'WE' },
    { label: 'Thursday', value: 'TH' },
    { label: 'Friday', value: 'FR' },
    { label: 'Saturday', value: 'SA' },
]

const nthChoices = [
    { label: '1st', value: 1 },
    { label: '2nd', value: 2 },
    { label: '3rd', value: 3 },
    { label: '4th', value: 4 },
    { label: '5th', value: 5 },
    { label: 'Last', value: -1 },
]

const freqChoices = [
    { label: 'Daily', value: 'DAILY' },
    { label: 'Weekly', value: 'WEEKLY' },
    { label: 'Monthly', value: 'MONTHLY' },
    { label: 'Yearly', value: 'YEARLY' }
]

const endChoices = [
    { label: 'Never', value: 'never' },
    { label: 'On date…', value: 'until' },
    { label: 'After N occurrences…', value: 'count' }
]

const defaultStart = props.preselectStart
    ? combineDateWithNextHalfHour(props.preselectStart)
    : nextHalfHour()

/** Form */
const form = useForm<Required<OrgEventDto>>({
    id: props.event?.id ?? undefined,
    title: props.event?.title ?? '',
    description: props.event?.description ?? '',
    location: props.event?.location ?? '',
    all_day: props.event?.all_day ?? false,
    start: props.event?.start ?? toLocalISO(defaultStart), // default 7pm
    end: props.event?.end ?? '',
    type_id: props.event?.type_id ?? (props.types[0]?.id ?? null),

    masons_only: props.event?.masons_only ?? false,
    open_to: (props.event?.open_to as any) ?? 'all',
    degree_required: (props.event?.degree_required as any) ?? 'none',
    is_public: props.event?.is_public ?? false,

    repeats: props.event?.repeats ?? false,
    rrule: props.event?.rrule ?? '',
    repeat_options: props.event?.repeat_options ?? {
        mode: 'none',
        nth: 1,
        weekday: 'TU',
        freq: 'MONTHLY',
        interval: 1,
        byweekday: ['TU'],
        ends: 'never',
        until: '',
        count: null
    }
})

function pad(n: number) { return (n < 10 ? '0' : '') + String(n)}

function toLocalISO(dt: Date) {
    return `${dt.getFullYear()}-${pad(dt.getMonth()+1)}-${pad(dt.getDate())}T${pad(dt.getHours())}:${pad(dt.getMinutes())}:${pad(dt.getSeconds())}`
}

/** Next half-hour from now in local time */
function nextHalfHour(base?: string | number | Date | null) {
    const d = base != null ? new Date(base as any) : new Date()
    d.setSeconds(0, 0)
    const m = d.getMinutes()
    const add = m === 0 || m <= 30 ? (30 - (m % 30 || 30)) : (60 - m)
    d.setMinutes(m + add)
    return d
}

/** If we got a YYYY-MM-DD (preselect), combine with next half-hour */
function combineDateWithNextHalfHour(yyyyMmDd: string) {
    const [y, m, d] = yyyyMmDd.split('-').map(Number)
    const base = new Date(y, (m - 1), d)
    return nextHalfHour(base)
}

/** UI: date models for Calendar (PrimeVue wants Date objects) */
function parseISOToDateLocal(v?: string | null) {
    if (!v) return null
    // v is local like 'YYYY-MM-DDTHH:mm:ss' -> construct a Date in local time
    const m = v.match(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})(?::(\d{2}))?$/)
    if (!m) return new Date(v) // fallback
    const [, Y, M, D, h, i, s] = m
    return new Date(
        Number(Y),
        Number(M) - 1,
        Number(D),
        Number(h),
        Number(i),
        s ? Number(s) : 0,
        0
    )
}

const startModel = computed({
    get: () => parseISOToDateLocal(form.start),
    set: (d: Date | null) => { form.start = d ? toLocalISO(d) : '' }
})

const endModel = computed({
    get: () => parseISOToDateLocal(form.end),
    set: (d: Date | null) => { form.end = d ? toLocalISO(d) : '' }
})

const untilModel = computed({
    get: () => parseISOToDateLocal(form.repeat_options?.until || ''),
    set: (d: Date | null) => { if (form.repeat_options) form.repeat_options.until = d ? toLocalISO(d).substring(0,10) : '' }
})

/** Generate RRULE string (stored in DB) from the chosen options */
function buildRRule(): string | '' {
    if (!form.repeats || !form.repeat_options || form.repeat_options.mode === 'none') return ''

    const tz = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC'
    const start = form.start ? new Date(form.start) : null
    const parts: string[] = []

    let rule = ''

    if (form.repeat_options.mode === 'nth-weekday') {
        const nth = form.repeat_options.nth || 1
        const wd = form.repeat_options.weekday || 'TU'
        const interval = form.repeat_options.interval || 1
        rule = `FREQ=MONTHLY;BYDAY=${wd};BYSETPOS=${nth};INTERVAL=${interval}`
    } else if (form.repeat_options.mode === 'interval') {
        const freq = form.repeat_options.freq || 'WEEKLY'
        const interval = form.repeat_options.interval || 1
        const byweekday = (form.repeat_options.byweekday && form.repeat_options.byweekday.length)
            ? `;BYDAY=${form.repeat_options.byweekday.join(',')}`
            : ''
        rule = `FREQ=${freq};INTERVAL=${interval}${byweekday}`
    }

    // End conditions
    if (form.repeat_options.ends === 'until' && form.repeat_options.until) {
        const u = (form.repeat_options.until as string).replace('-', '')
        rule += `;UNTIL=${u}T235959Z`
    } else if (form.repeat_options.ends === 'count' && form.repeat_options.count) {
        rule += `;COUNT=${form.repeat_options.count}`
    }

    // include timezone note as separate field? (We’ll store TZ in DB separately if you add a column)
    parts.push(rule)
    return parts.join('\n')
}

/** Submit */
function submit() {
    // build RRULE if repeating
    form.rrule = buildRRule()

    if (isEdit.value && form.id) {
        form.put(route('events.update', form.id), { preserveScroll: true })
    } else {
        form.post(route('events.store'), { preserveScroll: true })
    }
}

/** Convenience: if all_day, zero-out times (let backend treat end_at exclusive for multi-day) */
onMounted(() => {
    if (form.all_day) {
        // if only start provided, set end to same day (or leave blank for single-day)
        if (form.start && !form.end) {
            const s = new Date(form.start)
            s.setHours(0,0,0,0)
            form.start = s.toISOString()
        }
    }
})
</script>

<template>
    <AppLayout :title="pageTitle">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                    {{ pageTitle }}
                </h2>
                <div class="hidden sm:flex gap-2">
                    <Link :href="route('events.index')">
                        <Button icon="pi pi-arrow-left" label="Back" severity="secondary" text />
                    </Link>
                    <Button icon="pi pi-save" :label="isEdit ? 'Update' : 'Create'" @click="submit" />
                </div>
            </div>
        </template>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="sm:hidden mb-4 flex gap-2">
                <Link :href="route('events.index')">
                    <Button icon="pi pi-arrow-left" label="Back" severity="secondary" text class="w-full" />
                </Link>
                <Button icon="pi pi-save" :label="isEdit ? 'Update' : 'Create'" class="w-full" @click="submit" />
            </div>

            <Card class="shadow-lg rounded-xl overflow-hidden bg-white dark:bg-gray-900">
                <template #header>
                    <div class="px-6 sm:px-10 pt-8">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ isEdit ? 'Edit Event' : 'New Event' }}
                        </h3>
                        <Divider class="mt-4" />
                    </div>
                </template>

                <template #content>
                    <form class="px-6 sm:px-10 pb-8 space-y-8" @submit.prevent="submit">
                        <!-- Title & Type -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                                <InputText v-model="form.title" class="w-full mt-1" :invalid="!!form.errors.title" />
                                <p v-if="form.errors.title" class="mt-1 text-sm text-red-500">{{ form.errors.title }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                                <Select v-model="form.type_id" :options="types" optionLabel="name" optionValue="id" class="w-full mt-1" />
                            </div>
                        </div>

                        <!-- Date/Time -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start</label>
                                <DatePicker
                                    v-model="startModel"
                                    showIcon
                                    showTime
                                    hourFormat="12"
                                    class="w-full mt-1"
                                />
                            </div>
                            <div>
                                <div class="flex items-center justify-between">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">End (optional)</label>
                                    <div class="flex items-center gap-2">
                                        <ToggleSwitch v-model="form.all_day" inputId="all_day" />
                                        <label class="text-sm text-gray-700 dark:text-gray-300 select-none" for="all_day">All day</label>
                                    </div>
                                </div>
                                <DatePicker
                                    v-model="endModel"
                                    showIcon
                                    showTime
                                    hourFormat="12"
                                    class="w-full mt-1"
                                />
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Multi-day events will span on the calendar. Leave End blank for single-point events.
                                </p>
                            </div>
                        </div>

                        <!-- Location -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Location</label>
                            <InputText v-model="form.location" class="w-full mt-1" />
                        </div>

                        <!-- Visibility -->
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-6">
                            <div class="flex items-center gap-3">
                                <ToggleSwitch v-model="form.masons_only" inputId="mo" />
                                <label for="mo" class="text-sm text-gray-700 dark:text-gray-300">Masons only</label>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Open to</label>
                                <Select v-model="form.open_to" :options="openToOptions" optionLabel="label" optionValue="value" class="w-full mt-1" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Degree required</label>
                                <Select v-model="form.degree_required" :options="degreeOptions" optionLabel="label" optionValue="value" class="w-full mt-1" />
                            </div>
                            <div class="flex items-center gap-3">
                                <ToggleSwitch v-model="form.is_public" inputId="is_public" />
                                <label for="is_public" class="text-sm text-gray-700 dark:text-gray-300">Public</label>
                            </div>
                        </div>

                        <Divider />

                        <!-- Recurrence -->
                        <div>
                            <div class="flex items-center gap-3 mb-2">
                                <ToggleSwitch v-model="form.repeats" inputId="repeats" />
                                <label for="repeats" class="text-sm text-gray-700 dark:text-gray-300">Repeat</label>
                            </div>

                            <div v-if="form.repeats" class="space-y-6">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pattern</label>
                                        <Select
                                            v-model="form.repeat_options!.mode"
                                            :options="[
                        { label: 'Every Nth weekday (e.g., 3rd Tuesday)', value: 'nth-weekday' },
                        { label: 'Every X period', value: 'interval' }
                      ]"
                                            optionLabel="label"
                                            optionValue="value"
                                            class="w-full mt-1"
                                        />
                                    </div>

                                    <div v-if="form.repeat_options?.mode === 'nth-weekday'" class="sm:col-span-2 grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nth</label>
                                            <Select v-model="form.repeat_options!.nth" :options="nthChoices" optionLabel="label" optionValue="value" class="w-full mt-1" />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Weekday</label>
                                            <Select v-model="form.repeat_options!.weekday" :options="weekdayChoices" optionLabel="label" optionValue="value" class="w-full mt-1" />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Interval (months)</label>
                                            <InputNumber v-model="form.repeat_options!.interval" :min="1" :max="12" showButtons class="w-full mt-1" />
                                        </div>
                                    </div>

                                    <div v-if="form.repeat_options?.mode === 'interval'" class="sm:col-span-2 grid grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Every</label>
                                            <InputNumber v-model="form.repeat_options!.interval" :min="1" :max="365" showButtons class="w-full mt-1" />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Frequency</label>
                                            <Select v-model="form.repeat_options!.freq" :options="freqChoices" optionLabel="label" optionValue="value" class="w-full mt-1" />
                                        </div>
                                        <div v-if="form.repeat_options!.freq === 'WEEKLY'">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Weekdays</label>
                                            <MultiSelect v-model="form.repeat_options!.byweekday" :options="weekdayChoices" optionLabel="label" optionValue="value" display="chip" class="w-full mt-1" />
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ends</label>
                                        <Select v-model="form.repeat_options!.ends" :options="endChoices" optionLabel="label" optionValue="value" class="w-full mt-1" />
                                    </div>
                                    <div v-if="form.repeat_options?.ends === 'until'">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Until</label>
                                        <DatePicker v-model="untilModel" showIcon dateFormat="yy-mm-dd" class="w-full mt-1" />
                                    </div>
                                    <div v-if="form.repeat_options?.ends === 'count'">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Occurrences</label>
                                        <InputNumber v-model="form.repeat_options!.count" :min="1" :max="500" showButtons class="w-full mt-1" />
                                    </div>
                                </div>

                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Recurrence is stored as an RRULE. The calendar will expand occurrences automatically.
                                </p>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                            <div class="pv-editor rounded-md border border-[color:var(--p-content-border-color)] overflow-hidden mt-1">
                                <Editor v-model="form.description" class="pv-editor__inner" />
                            </div>
                            <p v-if="form.errors.description" class="mt-1 text-sm text-red-500">{{ form.errors.description }}</p>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end gap-2">
                            <Link :href="route('events.index')">
                                <Button label="Cancel" severity="secondary" text />
                            </Link>
                            <Button :label="isEdit ? 'Update' : 'Create'" icon="pi pi-save" @click="submit" />
                        </div>
                    </form>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>

<style scoped>
/* keep Editor styles consistent with your Quill overrides */
.pv-editor__inner :deep(.ql-container){ min-height: 12rem; }
</style>
