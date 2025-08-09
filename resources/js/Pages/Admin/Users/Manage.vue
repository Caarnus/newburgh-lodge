<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import MultiSelect from 'primevue/multiselect'
import Tag from 'primevue/tag'
import Card from 'primevue/card'

import ConfirmsPassword from '@/Components/ConfirmsPassword.vue'

type UserRow = {
    id: number
    name: string
    email: string
    roles: string[]
    isAdmin: boolean
}

const props = defineProps<{
    users: UserRow[]
    roles: string[]
    can: { isAdmin: boolean; isSecretary: boolean }
}>()

const isSecretaryOnly = computed(() => props.can.isSecretary && !props.can.isAdmin)

const selectedUser = ref<UserRow | null>(null)
const showCreate = ref(false)
const showPassword = ref(false)

const originals = ref<Record<number, {name:string; email:string; roles:string[]}>>({})
props.users.forEach(u => {
    originals.value[u.id] = { name: u.name, email: u.email, roles: [...u.roles] }
})

const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    roles: [] as string[],
})

const editForms = ref<Record<number, ReturnType<typeof useForm>>>({})
props.users.forEach(u => {
    editForms.value[u.id] = useForm({
        name: u.name,
        email: u.email,
        roles: [...u.roles],
    })
})

const pwdForm = ref<ReturnType<typeof useForm> | null>(null)

function sameRoles(a: string[] = [], b: string[] = []) {
    if (a.length !== b.length) return false
    const A = [...a].sort(), B = [...b].sort()
    for (let i = 0; i < A.length; i++) if (A[i] !== B[i]) return false
    return true
}

function snapshotRow(userId: number) {
    const f = editForms.value[userId].data()
    originals.value[userId] = { name: f.name, email: f.email, roles: [...(f.roles as string[])] }
}

function snapshotAllFrom(users: UserRow[]) {
    users.forEach(u => {
        const roles = [...u.roles]
        // ensure there is a form for the row, then snapshot
        if (!editForms.value[u.id]) {
            editForms.value[u.id] = useForm({ name: u.name, email: u.email, roles })
        } else {
            const form = editForms.value[u.id]
            // update defaults so future form.reset() matches server
            form.defaults({ name: u.name, email: u.email, roles })
            // update current reactive fields
            form.name = u.name
            form.email = u.email
            form.roles = roles
            form.clearErrors?.()
        }
        originals.value[u.id] = { name: u.name, email: u.email, roles }
    })
}

function isDirtyRow(userId: number) {
    const o = originals.value[userId]
    const f = editForms.value[userId].data()
    if (!o) return false
    return o.name !== f.name || o.email !== f.email || !sameRoles(o.roles, f.roles as string[])
}
const dirtyIds = computed(() => Object.keys(editForms.value)
    .map(Number)
    .filter(id => isDirtyRow(id))
)
const hasDirty = computed(() => dirtyIds.value.length > 0)

function openCreate() {
    createForm.reset()
    showCreate.value = true
}

function canEditRow(u: UserRow) {
    // Secretaries cannot modify Admins
    return !(isSecretaryOnly.value && u.isAdmin)
}

function openPassword(u: UserRow) {
    if (!canEditRow(u)) return
    selectedUser.value = u
    pwdForm.value = useForm({
        password: '',
        password_confirmation: '',
    })
    showPassword.value = true
}

function roleTooltip(userId: number) {
    const roles = editForms.value[userId].roles as unknown as string[]
    return roles?.length ? `Selected: ${roles.join(', ')}` : 'No roles selected'
}

// submit helpers (invoked after password is confirmed)
function doCreate() {
    createForm.post(route('admin.users.store'), {
        onSuccess: () => {
            showCreate.value = false;
            router.reload({
                only: ['users'],
                onSuccess: (page) => {
                    const fresh = (page.props as any).users as UserRow[]
                    snapshotAllFrom(fresh)
                }
            })
        }
    })
}
function doUpdate(userId: number) {
    editForms.value[userId].put(route('admin.users.update', userId), {
        onSuccess: () => {
            snapshotRow(userId)
            router.reload({
                only: ['users'],
            })
        }
    })
}
function doSetPassword(userId: number) {
    if (!pwdForm.value) return
    pwdForm.value.put(route('admin.users.setPassword', userId), {
        onSuccess: () => (showPassword.value = false),
    })
}

function buildBulkPayload() {
    return {
        items: dirtyIds.value.map(id => ({
            id,
            ...editForms.value[id].data(), // {name,email,roles}
        })),
    }
}

function doBulkSave() {
    const payload = buildBulkPayload()
    router.put(route('admin.users.bulkUpdate'), payload, {
        onSuccess: () => {
            // refresh users, then reset originals to new values
            router.reload({
                only: ['users'],
                onSuccess: (page) => {
                    const fresh = (page.props as any).users as UserRow[]
                    snapshotAllFrom(fresh)
                }
            })
        }
    })
}

watch(() => props.users, (u) => {
    if (u && Array.isArray(u)) snapshotAllFrom(u as UserRow[])
}, { immediate: true })
</script>

<template>
    <AppLayout title="User Management">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                    User Management
                </h2>
                <div class="flex items-center gap-2">
                    <!-- Save All (disabled if nothing dirty) -->
                    <ConfirmsPassword @confirmed="doBulkSave">
                        <Button
                            icon="pi pi-save"
                            aria-label="Save All"
                            :disabled="!hasDirty"
                            v-tooltip.bottom="hasDirty ? 'Save all changes' : 'No changes to save'"
                        />
                    </ConfirmsPassword>

                    <Button icon="pi pi-user-plus" label="Add User" @click="openCreate" />
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <Card class="bg-white dark:bg-gray-900 shadow">
                <template #content>
                    <DataTable :value="props.users" paginator :rows="10" striped-rows responsive-layout="scroll">
                        <Column field="name" header="Name" :sortable="true">
                            <template #body="{ data }">
                                <InputText v-model="editForms[data.id].name" class="w-full" :disabled="!canEditRow(data)" />
                            </template>
                        </Column>

                        <Column field="email" header="Email" :sortable="true">
                            <template #body="{ data }">
                                <InputText v-model="editForms[data.id].email" class="w-full" :disabled="!canEditRow(data)" />
                            </template>
                        </Column>

                        <Column header="Roles">
                            <template #body="{ data }">
                                <div class="flex items-center gap-2">
                                    <MultiSelect
                                        v-model="editForms[data.id].roles"
                                        :options="props.roles"
                                        class="w-full"
                                        display="chip"
                                        :disabled="!canEditRow(data)"
                                        placeholder="Select roles"
                                        :pt="{ root: { title: roleTooltip(data.id) }, label: { title: roleTooltip(data.id) } }"
                                    />
                                    <Tag v-if="data.isAdmin" value="Admin" severity="danger" />
                                </div>
                            </template>
                        </Column>

                        <Column header="Actions">
                            <template #body="{ data }">
                                <div class="flex gap-2">
                                    <ConfirmsPassword @confirmed="() => doUpdate(data.id)">
                                        <Button
                                            icon="pi pi-save"
                                            aria-label="Save"
                                            text rounded
                                            :disabled="!canEditRow(data)"
                                            v-tooltip.top="'Save changes'"
                                        />
                                    </ConfirmsPassword>

                                    <Button
                                        icon="pi pi-key"
                                        aria-label="Set Password"
                                        text rounded
                                        :disabled="!canEditRow(data)"
                                        @click="openPassword(data)"
                                        v-tooltip.top="'Set password'"
                                    />
                                </div>
                            </template>
                        </Column>
                        <Column header="">
                            <template #body="{ data }">
                                <span v-if="isDirtyRow(data.id)" class="inline-block h-2 w-2 rounded-full bg-amber-500" title="Changed"></span>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>
        </div>

        <!-- Create User Dialog -->
        <Dialog v-model:visible="showCreate" header="Add User" modal class="w-full sm:w-[32rem]">
            <div class="space-y-4">
                <div>
                    <label class="text-sm">Name</label>
                    <InputText v-model="createForm.name" class="w-full" />
                    <p v-if="createForm.errors.name" class="text-red-500 text-sm mt-1">{{ createForm.errors.name }}</p>
                </div>

                <div>
                    <label class="text-sm">Email</label>
                    <InputText v-model="createForm.email" class="w-full" />
                    <p v-if="createForm.errors.email" class="text-red-500 text-sm mt-1">{{ createForm.errors.email }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm">Password</label>
                        <Password v-model="createForm.password" :feedback="false" toggleMask class="w-full" />
                        <p v-if="createForm.errors.password" class="text-red-500 text-sm mt-1">{{ createForm.errors.password }}</p>
                    </div>
                    <div>
                        <label class="text-sm">Confirm</label>
                        <Password v-model="createForm.password_confirmation" :feedback="false" toggleMask class="w-full" />
                    </div>
                </div>

                <div>
                    <label class="text-sm">Roles</label>
                    <MultiSelect v-model="createForm.roles" :options="props.roles" display="chip" class="w-full" />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <Button icon="pi pi-times-circle" v-tooltip.top="'Cancel'" severity="secondary" text @click="showCreate=false" />
                    <ConfirmsPassword @confirmed="doCreate">
                        <Button icon="pi pi-check" aria-label="Create" v-tooltip.top="'Create user'" />
                    </ConfirmsPassword>
                </div>
            </div>
        </Dialog>

        <!-- Set Password Dialog -->
        <Dialog
            v-model:visible="showPassword"
            :header="`Set Password for ${selectedUser?.name ?? ''}`"
            modal
            class="w-full sm:w-[28rem]"
        >
            <div class="space-y-4">
                <div>
                    <label class="text-sm">New Password</label>
                    <Password v-model="pwdForm!.password" :feedback="false" toggleMask class="w-full" />
                    <p v-if="pwdForm?.errors.password" class="text-red-500 text-sm mt-1">{{ pwdForm?.errors.password }}</p>
                </div>
                <div>
                    <label class="text-sm">Confirm Password</label>
                    <Password v-model="pwdForm!.password_confirmation" :feedback="false" toggleMask class="w-full" />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <Button icon="pi pi-times-circle" aria-label="Cancel" v-tooltip.top="'Cancel'" severity="secondary" text @click="showPassword=false" />
                    <!-- Save password — wrapped in ConfirmsPassword -->
                    <ConfirmsPassword @confirmed="() => doSetPassword(selectedUser!.id)">
                        <Button icon="pi pi-save" aria-label="Save Password" v-tooltip.top="'Save password'" />
                    </ConfirmsPassword>
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>
