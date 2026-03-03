<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
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
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Textarea from 'primevue/textarea'
import Message from 'primevue/message'
import Dialog from 'primevue/dialog'

type OrgEventType = {
    id: number
    name: string
    color?: string | null
}

type RepeatOptions = {
    mode: 'none' | 'nth-weekday' | 'interval'
    nth?: number | null                 // 1..5 or -1 (last)
    weekday?: string | null             // 'MO'..'SU'
    freq?: 'DAILY' | 'WEEKLY' | 'MONTHLY' | 'YEARLY' | null
    interval?: number | null
    byweekday?: string[] | null         // only for WEEKLY interval
    ends?: 'never' | 'until' | 'count'
    until?: string | null               // YYYY-MM-DD
    count?: number | null
}

type OrgEventDto = {
    id?: number
    title?: string
    description?: string | null
    location?: string | null
    timezone?: string | null
    all_day?: boolean
    start_local?: string | null         // YYYY-MM-DDTHH:mm
    end_local?: string | null           // YYYY-MM-DDTHH:mm
    type_id?: number | null

    masons_only?: boolean
    open_to?: 'all' | 'members' | 'officers'
    degree_required?: 'none' | 'entered apprentice' | 'fellowcraft' | 'master mason'
    is_public?: boolean

    repeats?: boolean
    repeat_options?: RepeatOptions | null
}

const props = defineProps<{
    event?: OrgEventDto | null
    types: OrgEventType[]
    preselectStart?: string | null

    signupPage?: any | null
    occurrences?: { occurrence_starts_at: string; label_local: string }[]
    occurrenceOverrides?: {
        occurrence_starts_at: string
        override_starts_at: string
        override_ends_at?: string | null
        is_canceled: boolean
    }[]
}>()

const isEdit = computed(() => !!props.event?.id)
const pageTitle = computed(() => (isEdit.value ? 'Edit Event' : 'Create Event'))

/** ---------- helpers ---------- */
function pad2(n: number) {
    return (n < 10 ? '0' : '') + String(n)
}

function toLocalString(d: Date | null): string {
    if (!d) return ''
    return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}T${pad2(d.getHours())}:${pad2(
        d.getMinutes()
    )}`
}

function parseLocalString(s?: string | null): Date | null {
    if (!s) return null
    const m = s.match(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})$/)
    if (!m) return null
    const [, Y, M, D, h, i] = m
    return new Date(Number(Y), Number(M) - 1, Number(D), Number(h), Number(i), 0, 0)
}

function dateToYmd(d: Date | null): string | null {
    if (!d) return null
    return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`
}

function ymdToDate(ymd?: string | null): Date | null {
    if (!ymd) return null
    const m = ymd.match(/^(\d{4})-(\d{2})-(\d{2})$/)
    if (!m) return null
    const [, Y, M, D] = m
    return new Date(Number(Y), Number(M) - 1, Number(D), 0, 0, 0, 0)
}

function nextHalfHour(base?: Date): Date {
    const d = base ? new Date(base) : new Date()
    d.setSeconds(0, 0)
    const m = d.getMinutes()
    if (m === 0) d.setMinutes(30)
    else if (m <= 30) d.setMinutes(30)
    else d.setHours(d.getHours() + 1, 0, 0, 0)
    return d
}

function combineDateWithNextHalfHour(yyyyMmDd: string): Date {
    const [y, m, d] = yyyyMmDd.split('-').map(Number)
    return nextHalfHour(new Date(y, m - 1, d, 0, 0, 0, 0))
}

function weekdayFromDate(d: Date): 'SU' | 'MO' | 'TU' | 'WE' | 'TH' | 'FR' | 'SA' {
    // JS: 0=Sun..6=Sat
    return (['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'] as const)[d.getDay()]
}

const tzOptions = Intl.supportedValuesOf ? Intl.supportedValuesOf('timeZone') : ['America/Chicago', 'UTC']

const browserTz = (() => {
    try {
        return Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC'
    } catch {
        return 'UTC'
    }
})()

const defaultStartDate = props.preselectStart ? combineDateWithNextHalfHour(props.preselectStart) : nextHalfHour()

const defaultRepeatOptions: RepeatOptions = {
    mode: 'none',
    nth: 1,
    weekday: 'TU',
    freq: 'MONTHLY',
    interval: 1,
    byweekday: ['TU'],
    ends: 'never',
    until: null,
    count: null,
}

const form = useForm({
    id: props.event?.id ?? undefined,

    title: props.event?.title ?? '',
    description: props.event?.description ?? '',
    location: props.event?.location ?? '',
    timezone: props.event?.timezone ?? browserTz,
    all_day: props.event?.all_day ?? false,

    start_local: props.event?.start_local ?? toLocalString(defaultStartDate),
    end_local: props.event?.end_local ?? '',

    type_id: props.event?.type_id ?? (props.types[0]?.id ?? null),

    masons_only: props.event?.masons_only ?? false,
    open_to: (props.event?.open_to as any) ?? 'all',
    degree_required: (props.event?.degree_required as any) ?? 'none',
    is_public: props.event?.is_public ?? false,

    repeats: props.event?.repeats ?? false,
    repeat_options: (props.event?.repeat_options ?? defaultRepeatOptions) as RepeatOptions,
})

/** PrimeVue DatePickers use Date objects */
const startWall = ref<Date | null>(null)
const endWall = ref<Date | null>(null)
const untilWall = ref<Date | null>(null)

/** UI option lists */
const degreeOptions = [
    { label: 'None', value: 'none' },
    { label: 'Entered Apprentice', value: 'entered apprentice' },
    { label: 'Fellowcraft', value: 'fellowcraft' },
    { label: 'Master Mason', value: 'master mason' },
]

const openToOptions = [
    { label: 'All', value: 'all' },
    { label: 'Members', value: 'members' },
    { label: 'Officers', value: 'officers' },
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
    { label: 'Yearly', value: 'YEARLY' },
]

const endChoices = [
    { label: 'Never', value: 'never' },
    { label: 'On date…', value: 'until' },
    { label: 'After N occurrences…', value: 'count' },
]

function normalizeRepeatOptions() {
    if (!form.repeat_options) form.repeat_options = { ...defaultRepeatOptions }
    if (!form.repeats) {
        form.repeat_options.mode = 'none'
        form.repeat_options.ends = 'never'
        form.repeat_options.until = null
        form.repeat_options.count = null
        return
    }

    // when enabling repeats, make sure mode isn't 'none'
    if (form.repeat_options.mode === 'none') {
        form.repeat_options.mode = 'nth-weekday'
    }

    if (form.repeat_options.mode === 'nth-weekday') {
        form.repeat_options.freq = 'MONTHLY'
        form.repeat_options.byweekday = null
        form.repeat_options.interval = Math.max(1, Number(form.repeat_options.interval ?? 1))
        if (!form.repeat_options.weekday) {
            form.repeat_options.weekday = startWall.value ? weekdayFromDate(startWall.value) : 'TU'
        }
        form.repeat_options.nth = form.repeat_options.nth ?? 1
    }

    if (form.repeat_options.mode === 'interval') {
        form.repeat_options.interval = Math.max(1, Number(form.repeat_options.interval ?? 1))
        form.repeat_options.freq = (form.repeat_options.freq ?? 'WEEKLY') as any
        if (form.repeat_options.freq === 'WEEKLY') {
            if (!Array.isArray(form.repeat_options.byweekday) || form?.repeat_options.byweekday.length === 0) {
                form.repeat_options.byweekday = [startWall.value ? weekdayFromDate(startWall.value) : 'TU']
            }
        } else {
            form.repeat_options.byweekday = null
        }
    }

    // ends
    form.repeat_options.ends = (form.repeat_options.ends ?? 'never') as any
    if (form.repeat_options.ends !== 'until') form.repeat_options.until = null
    if (form.repeat_options.ends !== 'count') form.repeat_options.count = null
}

function submit() {
    // ensure form fields match picker values
    form.start_local = toLocalString(startWall.value)
    form.end_local = endWall.value ? toLocalString(endWall.value) : ''

    if (form.repeat_options?.ends === 'until') {
        form.repeat_options.until = dateToYmd(untilWall.value)
    }

    normalizeRepeatOptions()

    if (isEdit.value && form.id) {
        form.put(route('events.update', form.id), { preserveScroll: true })
    } else {
        form.post(route('events.store'), { preserveScroll: true })
    }
}

/** All-day tweaks: clamp to day start/end in the UI */
watch(
    () => form.all_day,
    (isAll) => {
        if (!startWall.value) return

        if (isAll) {
            const s = new Date(startWall.value)
            s.setHours(0, 0, 0, 0)
            startWall.value = s

            const e = endWall.value ? new Date(endWall.value) : new Date(s)
            e.setFullYear(s.getFullYear(), s.getMonth(), s.getDate())
            e.setHours(23, 59, 0, 0)
            endWall.value = e
        }
    }
)

/** Keep local strings updated from DatePickers */
watch(startWall, (d) => {
    if (!d) return
    form.start_local = toLocalString(d)
    // If repeats weekly and byweekday empty, seed it
    if (form.repeats && form.repeat_options?.mode === 'interval' && form.repeat_options.freq === 'WEEKLY') {
        if (!form.repeat_options.byweekday || form.repeat_options.byweekday.length === 0) {
            form.repeat_options.byweekday = [weekdayFromDate(d)]
        }
    }
})

watch(endWall, (d) => {
    form.end_local = d ? toLocalString(d) : ''
})

watch(
    () => form.repeats,
    () => normalizeRepeatOptions()
)

watch(
    () => form.repeat_options?.mode,
    () => normalizeRepeatOptions()
)

watch(
    () => form.repeat_options?.freq,
    () => normalizeRepeatOptions()
)

watch(untilWall, (d) => {
    if (!form.repeat_options) return
    form.repeat_options.until = dateToYmd(d)
})

/** ---------- Signup Page (unchanged, still PrimeVue) ---------- */
const signupCoverPreview = ref<string | null>(props.signupPage?.cover_image_url ?? null)

const signupForm = useForm({
    is_enabled: props.signupPage?.is_enabled ?? false,
    slug: props.signupPage?.slug ?? '',
    title_override: props.signupPage?.title_override ?? '',
    description: props.signupPage?.description ?? '',
    capacity: props.signupPage?.capacity ?? null,
    opens_at: props.signupPage?.opens_at ?? '',
    closes_at: props.signupPage?.closes_at ?? '',
    confirmation_message: props.signupPage?.confirmation_message ?? '',
    cover_image: null as File | null,
    remove_cover_image: false,
})

function pickSignupCover(e: any) {
    const file = e.target.files?.[0]
    if (!file) return
    signupForm.cover_image = file
    signupForm.remove_cover_image = false
    signupCoverPreview.value = URL.createObjectURL(file)
}

function removeSignupCover() {
    signupForm.cover_image = null
    signupForm.remove_cover_image = true
    signupCoverPreview.value = null
}

function saveSignupPage() {
    signupForm.post(route('events.signup-page.upsert', form.id), {
        preserveScroll: true,
        forceFormData: true,
        errorBag: 'signupPage',
    })
}

function deleteSignupPage() {
    if (!confirm('Delete the signup page? This will remove signups/reminders tied to it.')) return
    signupForm.delete(route('events.signup-page.destroy', form.id), {
        preserveScroll: true,
    })
}

/** ---------- Occurrence Overrides (still UTC ISO, unchanged) ---------- */
const occurrenceDialog = ref(false)
const selectedOccurrence = ref<{ occurrence_starts_at: string; label_local: string } | null>(null)

const overridesMap = computed(() => {
    const m = new Map<string, any>()
    for (const o of props.occurrenceOverrides ?? []) m.set(o.occurrence_starts_at, o)
    return m
})

const overrideForm = useForm({
    occurrence_starts_at: '',
    override_starts_at: '',
    override_ends_at: '',
    is_canceled: false,
})

const overrideStartModel = computed({
    get: () => (overrideForm.override_starts_at ? new Date(overrideForm.override_starts_at) : null),
    set: (d) => {
        overrideForm.override_starts_at = d ? new Date(d as any).toISOString() : ''
    },
})

const overrideEndModel = computed({
    get: () => (overrideForm.override_ends_at ? new Date(overrideForm.override_ends_at) : null),
    set: (d) => {
        overrideForm.override_ends_at = d ? new Date(d as any).toISOString() : ''
    },
})

function openOverride(row: any) {
    selectedOccurrence.value = row
    const existing = overridesMap.value.get(row.occurrence_starts_at)

    overrideForm.occurrence_starts_at = row.occurrence_starts_at
    overrideForm.is_canceled = existing?.is_canceled ?? false
    overrideForm.override_starts_at = existing?.override_starts_at ?? row.occurrence_starts_at
    overrideForm.override_ends_at = existing?.override_ends_at ?? ''
    occurrenceDialog.value = true
}

function saveOverride() {
    overrideForm.post(route('events.occurrence-overrides.upsert', form.id), {
        preserveScroll: true,
        errorBag: 'occurrenceOverride',
        onSuccess: () => (occurrenceDialog.value = false),
    })
}

function clearOverride(row: any) {
    if (!confirm('Remove override for this occurrence?')) return
    useForm({ occurrence_starts_at: row.occurrence_starts_at }).delete(route('events.occurrence-overrides.destroy', form.id), {
        preserveScroll: true,
        errorBag: 'occurrenceOverride',
    })
}

/** init pickers from form values */
onMounted(() => {
    startWall.value = parseLocalString(form.start_local) ?? defaultStartDate
    endWall.value = parseLocalString(form.end_local) ?? null

    // until
    if (form.repeat_options?.ends === 'until' && form.repeat_options.until) {
        untilWall.value = ymdToDate(form.repeat_options.until)
    } else {
        untilWall.value = null
    }

    normalizeRepeatOptions()

    // normalize all-day on mount
    if (form.all_day && startWall.value) {
        const s = new Date(startWall.value)
        s.setHours(0, 0, 0, 0)
        startWall.value = s

        if (!endWall.value) {
            const e = new Date(s)
            e.setHours(23, 59, 0, 0)
            endWall.value = e
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
                                <DatePicker v-model="startWall" showIcon showTime hourFormat="12" class="w-full mt-1" />
                                <p v-if="form.errors.start_local" class="mt-1 text-sm text-red-500">{{ form.errors.start_local }}</p>
                            </div>
                            <div>
                                <div class="flex items-center justify-between">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">End (optional)</label>
                                    <div class="flex items-center gap-2">
                                        <ToggleSwitch v-model="form.all_day" inputId="all_day" />
                                        <label class="text-sm text-gray-700 dark:text-gray-300 select-none" for="all_day">All day</label>
                                    </div>
                                </div>
                                <DatePicker v-model="endWall" showIcon showTime hourFormat="12" class="w-full mt-1" />
                                <p v-if="form.errors.end_local" class="mt-1 text-sm text-red-500">{{ form.errors.end_local }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Multi-day events will span on the calendar. Leave End blank for single-point events.
                                </p>
                            </div>
                        </div>

                        <!-- Location / TZ -->
                        <div class="flex flex-row w-full gap-2">
                            <div class="w-2/3">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Location</label>
                                <InputText v-model="form.location" class="w-full mt-1" />
                            </div>
                            <div class="w-1/3">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Time zone</label>
                                <Select v-model="form.timezone" :options="tzOptions" class="w-full mt-1" />
                            </div>
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
                                <label for="is_public" class="text-sm text-gray-700 dark:text-gray-300">Publish</label>
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
                                            v-model="form.repeat_options.mode"
                                            :options="[
                        { label: 'Every Nth weekday (e.g., 3rd Tuesday)', value: 'nth-weekday' },
                        { label: 'Every X period', value: 'interval' }
                      ]"
                                            optionLabel="label"
                                            optionValue="value"
                                            class="w-full mt-1"
                                        />
                                    </div>

                                    <div v-if="form.repeat_options.mode === 'nth-weekday'" class="sm:col-span-2 grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nth</label>
                                            <Select v-model="form.repeat_options.nth" :options="nthChoices" optionLabel="label" optionValue="value" class="w-full mt-1" />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Weekday</label>
                                            <Select v-model="form.repeat_options.weekday" :options="weekdayChoices" optionLabel="label" optionValue="value" class="w-full mt-1" />
                                        </div>
                                    </div>

                                    <div v-if="form.repeat_options.mode === 'interval'" class="sm:col-span-2 grid grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Frequency</label>
                                            <Select v-model="form.repeat_options.freq" :options="freqChoices" optionLabel="label" optionValue="value" class="w-full mt-1" />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Interval</label>
                                            <InputNumber v-model="form.repeat_options.interval" :min="1" :max="999" showButtons class="w-full mt-1" />
                                        </div>
                                        <div v-if="form.repeat_options.freq === 'WEEKLY'">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Weekdays</label>
                                            <MultiSelect
                                                v-model="form.repeat_options.byweekday"
                                                :options="weekdayChoices"
                                                optionLabel="label"
                                                optionValue="value"
                                                display="chip"
                                                class="w-full mt-1"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ends</label>
                                        <Select v-model="form.repeat_options.ends" :options="endChoices" optionLabel="label" optionValue="value" class="w-full mt-1" />
                                    </div>

                                    <div v-if="form.repeat_options.ends === 'until'">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Until</label>
                                        <!-- DATE ONLY (backend treats as end-of-day in TZ) -->
                                        <DatePicker v-model="untilWall" showIcon class="w-full mt-1" />
                                    </div>

                                    <div v-if="form.repeat_options.ends === 'count'">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Occurrences</label>
                                        <InputNumber v-model="form.repeat_options.count" :min="1" :max="10000" showButtons class="w-full mt-1" />
                                    </div>
                                </div>

                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Recurrence is generated and expanded on the backend. Vue only sends repeat options.
                                </p>
                            </div>
                        </div>

                        <Divider />

                        <!-- Signup Page -->
                        <div v-if="isEdit" class="space-y-4">
                            <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100">Signup Page</h4>

                            <div class="flex items-center gap-3">
                                <ToggleSwitch v-model="signupForm.is_enabled" inputId="signup_enabled" />
                                <label for="signup_enabled" class="text-sm text-gray-700 dark:text-gray-300">
                                    Enable signup page for this event
                                </label>
                            </div>

                            <Message v-if="signupForm.recentlySuccessful" severity="success">
                                Signup page saved.
                            </Message>

                            <div v-if="signupForm.is_enabled" class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Slug</label>
                                    <InputText v-model="signupForm.slug" class="w-full mt-1" :invalid="!!signupForm.errors.slug" />
                                    <p v-if="signupForm.errors.slug" class="mt-1 text-sm text-red-500">{{ signupForm.errors.slug }}</p>

                                    <p v-if="signupForm.slug" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                        Public URL:
                                        <a :href="route('public.signup.show', signupForm.slug)" target="_blank" class="underline">
                                            {{ route('public.signup.show', signupForm.slug) }}
                                        </a>
                                    </p>
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title override (optional)</label>
                                    <InputText v-model="signupForm.title_override" class="w-full mt-1" />
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                                    <Textarea v-model="signupForm.description" rows="5" class="w-full mt-1" />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Capacity (optional)</label>
                                    <InputNumber v-model="signupForm.capacity" class="w-full mt-1" :min="1" showButtons />
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirmation message (optional)</label>
                                    <Textarea v-model="signupForm.confirmation_message" rows="3" class="w-full mt-1" />
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cover image (optional)</label>

                                    <div v-if="signupCoverPreview" class="mt-2">
                                        <img alt="Cover Image" :src="signupCoverPreview" class="w-full max-h-60 object-cover rounded-lg border border-gray-200 dark:border-gray-700" />
                                        <Button class="mt-2" label="Remove cover" severity="secondary" text icon="pi pi-trash" @click="removeSignupCover" />
                                    </div>

                                    <input type="file" accept="image/*" class="mt-2" @change="pickSignupCover" />
                                    <p v-if="signupForm.errors.cover_image" class="mt-1 text-sm text-red-500">{{ signupForm.errors.cover_image }}</p>
                                </div>

                                <div class="sm:col-span-2 flex gap-2">
                                    <Button label="Save Signup Page" icon="pi pi-save" :loading="signupForm.processing" @click="saveSignupPage" />
                                    <Button
                                        v-if="props.signupPage"
                                        label="Delete Signup Page"
                                        icon="pi pi-times"
                                        severity="danger"
                                        text
                                        @click="deleteSignupPage"
                                    />
                                </div>
                            </div>
                        </div>

                        <Divider />

                        <!-- Occurrence Overrides -->
                        <div v-if="isEdit && form.repeats" class="space-y-4">
                            <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100">Occurrence Overrides</h4>

                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                Adjust the date/time for a single instance of this recurring event (or cancel one instance).
                            </p>

                            <DataTable :value="props.occurrences ?? []" class="p-datatable-sm">
                                <Column header="Occurrence">
                                    <template #body="{ data }">
                                        <div class="font-medium">{{ data.label_local }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ data.occurrence_starts_at }}</div>
                                    </template>
                                </Column>

                                <Column header="Override" style="width: 140px;">
                                    <template #body="{ data }">
                                        <span v-if="overridesMap.has(data.occurrence_starts_at)" class="text-emerald-700 font-medium">Yes</span>
                                        <span v-else class="text-gray-500">—</span>
                                    </template>
                                </Column>

                                <Column header="Actions" style="width: 260px;">
                                    <template #body="{ data }">
                                        <div class="flex gap-2">
                                            <Button label="Adjust" size="small" icon="pi pi-pencil" @click="openOverride(data)" />
                                            <Button
                                                v-if="overridesMap.has(data.occurrence_starts_at)"
                                                label="Clear"
                                                size="small"
                                                severity="secondary"
                                                text
                                                icon="pi pi-trash"
                                                @click="clearOverride(data)"
                                            />
                                        </div>
                                    </template>
                                </Column>
                            </DataTable>

                            <Dialog v-model:visible="occurrenceDialog" modal header="Adjust occurrence" class="w-full sm:w-[42rem]">
                                <div class="space-y-4">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">
                                        <span class="font-medium">Original:</span>
                                        {{ selectedOccurrence?.label_local }}
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <ToggleSwitch v-model="overrideForm.is_canceled" inputId="cancel_occ" />
                                        <label for="cancel_occ" class="text-sm text-gray-700 dark:text-gray-300">Cancel this instance</label>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">New start</label>
                                        <DatePicker v-model="overrideStartModel" showIcon showTime hourFormat="12" class="w-full mt-1" />
                                        <p v-if="overrideForm.errors.override_starts_at" class="mt-1 text-sm text-red-500">
                                            {{ overrideForm.errors.override_starts_at }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">New end (optional)</label>
                                        <DatePicker v-model="overrideEndModel" showIcon showTime hourFormat="12" class="w-full mt-1" />
                                    </div>

                                    <div class="flex justify-end gap-2 pt-2">
                                        <Button label="Cancel" severity="secondary" text @click="occurrenceDialog = false" />
                                        <Button label="Save" icon="pi pi-save" :loading="overrideForm.processing" @click="saveOverride" />
                                    </div>
                                </div>
                            </Dialog>
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
.pv-editor__inner :deep(.ql-container) {
    min-height: 12rem;
}
</style>
