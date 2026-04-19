<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { computed, ref } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";

import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import InputNumber from "primevue/inputnumber";
import Select from "primevue/select";
import Textarea from "primevue/textarea";
import Checkbox from "primevue/checkbox";
import Tag from "primevue/tag";

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
    availabilityOptions: {
        type: Array,
        default: () => [],
    },
    settings: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);

const settingsForm = useForm({
    order_notification_name: props.settings.order_notification_name ?? "",
    order_notification_email: props.settings.order_notification_email ?? "",
});

const dialogVisible = ref(false);
const editingItemId = ref(null);
const currentItemImageUrl = ref(null);
const sizeOptionsText = ref("");
const priceDollars = ref(0);

const itemForm = useForm({
    name: "",
    description: "",
    availability: "on_hand",
    price_cents: 0,
    requires_size: false,
    size_options: [],
    is_limited_edition: false,
    stock_remaining: null,
    is_active: true,
    sort_order: 0,
    image: null,
    remove_image: false,
});

const resetItemForm = () => {
    editingItemId.value = null;
    currentItemImageUrl.value = null;
    sizeOptionsText.value = "";
    priceDollars.value = 0;
    itemForm.reset();
    itemForm.clearErrors();
    itemForm.availability = "on_hand";
    itemForm.requires_size = false;
    itemForm.is_limited_edition = false;
    itemForm.is_active = true;
    itemForm.sort_order = 0;
    itemForm.image = null;
    itemForm.remove_image = false;
};

const openCreateDialog = () => {
    resetItemForm();
    dialogVisible.value = true;
};

const openEditDialog = (item) => {
    editingItemId.value = item.id;
    currentItemImageUrl.value = item.image_url ?? null;
    itemForm.name = item.name ?? "";
    itemForm.description = item.description ?? "";
    itemForm.availability = item.availability ?? "on_hand";
    itemForm.requires_size = !!item.requires_size;
    itemForm.is_limited_edition = !!item.is_limited_edition;
    itemForm.stock_remaining = item.stock_remaining;
    itemForm.is_active = !!item.is_active;
    itemForm.sort_order = Number(item.sort_order ?? 0);
    itemForm.image = null;
    itemForm.remove_image = false;
    priceDollars.value = Number(item.price_cents ?? 0) / 100;
    sizeOptionsText.value = Array.isArray(item.size_options) ? item.size_options.join(", ") : "";
    itemForm.clearErrors();
    dialogVisible.value = true;
};

const onItemImageSelected = (event) => {
    const input = event.target;
    itemForm.image = input?.files?.[0] ?? null;
    if (itemForm.image) {
        itemForm.remove_image = false;
    }
};

const normalizeItemPayload = () => {
    const sizeOptions = sizeOptionsText.value
        .split(",")
        .map((value) => value.trim())
        .filter((value) => value.length > 0);

    return {
        ...itemForm.data(),
        price_cents: Math.max(0, Math.round((Number(priceDollars.value) || 0) * 100)),
        size_options: sizeOptions,
        sort_order: Number(itemForm.sort_order ?? 0),
        stock_remaining: itemForm.stock_remaining === "" || itemForm.stock_remaining === null
            ? null
            : Number(itemForm.stock_remaining),
        remove_image: Boolean(itemForm.remove_image && !itemForm.image),
    };
};

const submitItem = () => {
    if (editingItemId.value) {
        itemForm.transform(() => ({
            ...normalizeItemPayload(),
            _method: "put",
        }));

        itemForm.post(route("admin.merchandise.items.update", editingItemId.value), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                dialogVisible.value = false;
                resetItemForm();
            },
            onFinish: () => itemForm.transform((data) => data),
        });

        return;
    }

    itemForm.transform(() => normalizeItemPayload());

    itemForm.post(route("admin.merchandise.items.store"), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            dialogVisible.value = false;
            resetItemForm();
        },
        onFinish: () => itemForm.transform((data) => data),
    });
};

const deleteItem = (item) => {
    if (!confirm(`Delete "${item.name}"?`)) {
        return;
    }

    router.delete(route("admin.merchandise.items.destroy", item.id), {
        preserveScroll: true,
    });
};

const saveSettings = () => {
    settingsForm.patch(route("admin.merchandise.settings.update"), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout title="Merchandise Catalog">
        <template #header>
            <h2 class="font-semibold text-xl leading-tight">
                Merchandise Catalog Management
            </h2>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
            <div
                v-if="flashSuccess"
                class="p-3 rounded-md border border-green-200 bg-green-50 text-green-900 dark:border-green-900/40 dark:bg-green-950/30 dark:text-green-100"
            >
                {{ flashSuccess }}
            </div>

            <section class="bg-surface-0 dark:bg-surface-900 shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold">Notification Settings</h3>
                <p class="text-sm opacity-80 mt-1">
                    Order and pre-order emails are sent to this address.
                </p>

                <form class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="saveSettings">
                    <div class="flex flex-col">
                        <label class="font-medium">Recipient Name</label>
                        <InputText v-model="settingsForm.order_notification_name" class="mt-1" />
                        <small v-if="settingsForm.errors.order_notification_name" class="mt-1 text-red-600 dark:text-red-400">
                            {{ settingsForm.errors.order_notification_name }}
                        </small>
                    </div>

                    <div class="flex flex-col">
                        <label class="font-medium">Recipient Email</label>
                        <InputText v-model="settingsForm.order_notification_email" class="mt-1" type="email" />
                        <small v-if="settingsForm.errors.order_notification_email" class="mt-1 text-red-600 dark:text-red-400">
                            {{ settingsForm.errors.order_notification_email }}
                        </small>
                    </div>

                    <div class="md:col-span-2">
                        <Button label="Save notification settings" type="submit" :loading="settingsForm.processing" />
                    </div>
                </form>
            </section>

            <section class="bg-surface-0 dark:bg-surface-900 shadow rounded-lg p-6">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold">Merchandise Items</h3>
                    <Button label="Add Item" icon="pi pi-plus" @click="openCreateDialog" />
                </div>

                <DataTable class="mt-4" :value="items" stripedRows paginator :rows="15" responsive-layout="scroll">
                    <Column header="Image">
                        <template #body="{ data }">
                            <img
                                v-if="data.image_url"
                                :src="data.image_url"
                                :alt="`${data.name} image`"
                                class="h-10 w-10 rounded object-cover border border-surface-200 dark:border-surface-700"
                            />
                            <span v-else>—</span>
                        </template>
                    </Column>
                    <Column field="name" header="Name" />
                    <Column field="availability_label" header="Type" />
                    <Column field="price_display" header="Price" />
                    <Column header="Flags">
                        <template #body="{ data }">
                            <div class="flex flex-wrap gap-1">
                                <Tag v-if="data.is_active" value="Active" severity="success" />
                                <Tag v-else value="Inactive" severity="secondary" />
                                <Tag v-if="data.requires_size" value="Sized" />
                                <Tag v-if="data.is_limited_edition" value="Limited" severity="warning" />
                            </div>
                        </template>
                    </Column>
                    <Column header="Stock">
                        <template #body="{ data }">
                            <span v-if="data.stock_remaining !== null">{{ data.stock_remaining }}</span>
                            <span v-else>—</span>
                        </template>
                    </Column>
                    <Column field="sort_order" header="Sort" />
                    <Column header="Actions">
                        <template #body="{ data }">
                            <div class="flex gap-2">
                                <Button icon="pi pi-pencil" text rounded @click="openEditDialog(data)" />
                                <Button icon="pi pi-trash" text rounded severity="danger" @click="deleteItem(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </section>
        </div>

        <Dialog
            v-model:visible="dialogVisible"
            :header="editingItemId ? 'Edit Merchandise Item' : 'Create Merchandise Item'"
            modal
            class="w-full sm:w-[52rem]"
        >
            <form class="space-y-4" @submit.prevent="submitItem">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col">
                        <label class="font-medium">Name</label>
                        <InputText v-model="itemForm.name" class="mt-1" />
                        <small v-if="itemForm.errors.name" class="mt-1 text-red-600 dark:text-red-400">{{ itemForm.errors.name }}</small>
                    </div>

                    <div class="flex flex-col">
                        <label class="font-medium">Type</label>
                        <Select
                            v-model="itemForm.availability"
                            class="mt-1"
                            :options="availabilityOptions"
                            optionLabel="label"
                            optionValue="value"
                        />
                        <small v-if="itemForm.errors.availability" class="mt-1 text-red-600 dark:text-red-400">{{ itemForm.errors.availability }}</small>
                    </div>

                    <div class="flex flex-col md:col-span-2">
                        <label class="font-medium">Description</label>
                        <Textarea v-model="itemForm.description" rows="3" class="mt-1" autoResize />
                        <small v-if="itemForm.errors.description" class="mt-1 text-red-600 dark:text-red-400">{{ itemForm.errors.description }}</small>
                    </div>

                    <div class="flex flex-col md:col-span-2">
                        <label class="font-medium">Item Photo</label>
                        <input
                            type="file"
                            accept="image/*"
                            class="mt-1"
                            @change="onItemImageSelected"
                        />
                        <small v-if="itemForm.errors.image" class="mt-1 text-red-600 dark:text-red-400">{{ itemForm.errors.image }}</small>
                        <div v-if="currentItemImageUrl && !itemForm.remove_image && !itemForm.image" class="mt-2">
                            <img :src="currentItemImageUrl" alt="Current item image" class="h-20 w-20 rounded object-cover border border-surface-200 dark:border-surface-700" />
                        </div>
                        <div v-if="itemForm.image" class="mt-1 text-xs opacity-80">
                            Selected file: {{ itemForm.image.name }}
                        </div>
                        <div v-if="currentItemImageUrl" class="mt-2 flex items-center gap-2">
                            <Checkbox v-model="itemForm.remove_image" inputId="item_remove_image" binary :disabled="!!itemForm.image" />
                            <label for="item_remove_image">Remove current photo</label>
                        </div>
                    </div>

                    <div class="flex flex-col">
                        <label class="font-medium">Price</label>
                        <InputNumber
                            v-model="priceDollars"
                            mode="currency"
                            currency="USD"
                            locale="en-US"
                            :min="0"
                            class="mt-1"
                        />
                        <small v-if="itemForm.errors.price_cents" class="mt-1 text-red-600 dark:text-red-400">{{ itemForm.errors.price_cents }}</small>
                    </div>

                    <div class="flex flex-col">
                        <label class="font-medium">Sort Order</label>
                        <InputNumber v-model="itemForm.sort_order" :min="0" :max="1000000" class="mt-1" />
                        <small v-if="itemForm.errors.sort_order" class="mt-1 text-red-600 dark:text-red-400">{{ itemForm.errors.sort_order }}</small>
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox v-model="itemForm.is_active" inputId="item_is_active" binary />
                        <label for="item_is_active">Active</label>
                    </div>

                    <div
                        v-if="itemForm.availability === 'on_hand'"
                        class="flex items-center gap-2"
                    >
                        <Checkbox v-model="itemForm.requires_size" inputId="item_requires_size" binary />
                        <label for="item_requires_size">Requires Size</label>
                    </div>

                    <div
                        v-if="itemForm.availability === 'on_hand'"
                        class="flex items-center gap-2"
                    >
                        <Checkbox v-model="itemForm.is_limited_edition" inputId="item_is_limited" binary />
                        <label for="item_is_limited">Limited Edition</label>
                    </div>

                    <div v-if="itemForm.availability === 'on_hand' && itemForm.requires_size" class="flex flex-col md:col-span-2">
                        <label class="font-medium">Size Options (comma separated)</label>
                        <InputText v-model="sizeOptionsText" class="mt-1" placeholder="S, M, L, XL" />
                        <small v-if="itemForm.errors.size_options" class="mt-1 text-red-600 dark:text-red-400">{{ itemForm.errors.size_options }}</small>
                    </div>

                    <div v-if="itemForm.availability === 'on_hand' && itemForm.is_limited_edition" class="flex flex-col">
                        <label class="font-medium">Stock Remaining</label>
                        <InputNumber v-model="itemForm.stock_remaining" :min="0" :max="1000000" class="mt-1" />
                        <small v-if="itemForm.errors.stock_remaining" class="mt-1 text-red-600 dark:text-red-400">{{ itemForm.errors.stock_remaining }}</small>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <Button label="Cancel" severity="secondary" text type="button" @click="dialogVisible = false" />
                    <Button :label="editingItemId ? 'Update Item' : 'Create Item'" type="submit" :loading="itemForm.processing" />
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>
