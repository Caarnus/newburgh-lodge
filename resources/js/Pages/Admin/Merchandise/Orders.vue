<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { computed, reactive, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";

import Button from "primevue/button";
import Select from "primevue/select";
import Tag from "primevue/tag";

const props = defineProps({
    orders: {
        type: Array,
        default: () => [],
    },
    statusOptions: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const selectedStatusByOrderId = reactive({});

const hydrateStatuses = () => {
    for (const order of props.orders) {
        selectedStatusByOrderId[order.id] = order.status;
    }
};

hydrateStatuses();
watch(() => props.orders, hydrateStatuses);

const updateStatus = (order) => {
    router.patch(
        route("admin.merchandise.orders.update-status", order.id),
        { status: selectedStatusByOrderId[order.id] ?? order.status },
        { preserveScroll: true }
    );
};

const statusSeverity = (status) => {
    if (status === "submitted") return "secondary";
    if (status === "confirmed") return "info";
    if (status === "payment_received") return "warning";
    if (status === "delivered") return "success";
    return "secondary";
};
</script>

<template>
    <AppLayout title="Merchandise Orders">
        <template #header>
            <h2 class="font-semibold text-xl leading-tight">
                Merchandise Orders
            </h2>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-4">
            <div
                v-if="flashSuccess"
                class="p-3 rounded-md border border-green-200 bg-green-50 text-green-900 dark:border-green-900/40 dark:bg-green-950/30 dark:text-green-100"
            >
                {{ flashSuccess }}
            </div>

            <div
                v-for="order in orders"
                :key="order.id"
                class="bg-surface-0 dark:bg-surface-900 rounded-lg shadow p-5 space-y-4"
            >
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold">
                            Order #{{ order.id }} - {{ order.order_type_label }}
                        </h3>
                        <p class="text-sm opacity-80">
                            Submitted: {{ order.submitted_at || "—" }}
                        </p>
                        <p class="text-sm opacity-80">
                            Customer: {{ order.customer_name || "(not provided)" }} | {{ order.customer_email }}
                        </p>
                        <p class="text-sm opacity-80">
                            Phone: {{ order.customer_phone || "(not provided)" }}
                        </p>
                        <p v-if="order.created_by_user" class="text-xs opacity-70 mt-1">
                            Linked account: {{ order.created_by_user.name }} ({{ order.created_by_user.email }})
                        </p>
                    </div>
                    <div class="text-right">
                        <Tag :value="order.status_label" :severity="statusSeverity(order.status)" />
                        <p class="font-semibold mt-2">{{ order.total_display }}</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left border-b border-surface-200 dark:border-surface-700">
                                <th class="py-2 pr-4">Item</th>
                                <th class="py-2 pr-4">Qty</th>
                                <th class="py-2 pr-4">Size</th>
                                <th class="py-2 pr-4">Unit</th>
                                <th class="py-2 pr-4">Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in order.items" :key="item.id" class="border-b border-surface-100 dark:border-surface-800">
                                <td class="py-2 pr-4">{{ item.item_name }}</td>
                                <td class="py-2 pr-4">{{ item.quantity }}</td>
                                <td class="py-2 pr-4">{{ item.size || "—" }}</td>
                                <td class="py-2 pr-4">{{ item.unit_price_display }}</td>
                                <td class="py-2 pr-4">{{ item.line_total_display }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p v-if="order.notes" class="text-sm">
                    <span class="font-medium">Notes:</span> {{ order.notes }}
                </p>

                <div class="flex flex-col md:flex-row md:items-center gap-2">
                    <Select
                        v-model="selectedStatusByOrderId[order.id]"
                        :options="statusOptions"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full md:w-72"
                    />
                    <Button label="Update Status" @click="updateStatus(order)" />
                </div>
            </div>

            <div v-if="orders.length === 0" class="bg-surface-0 dark:bg-surface-900 rounded-lg shadow p-5">
                No merchandise orders yet.
            </div>
        </div>
    </AppLayout>
</template>

