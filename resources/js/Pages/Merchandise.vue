<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { computed, reactive, watch } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";

import InputText from "primevue/inputtext";
import InputNumber from "primevue/inputnumber";
import Textarea from "primevue/textarea";
import Button from "primevue/button";
import Select from "primevue/select";

const page = usePage();
const prefillEmail = String(page.props.prefillEmail ?? page.props.auth?.user?.email ?? "");

const flashSuccess = computed(() => page.props.flash?.success);

const availableItems = computed(() => page.props.availableItems ?? []);
const preorderItems = computed(() => page.props.preorderItems ?? []);

const addSelections = reactive({});
const addQuantities = reactive({});
const addErrors = reactive({});

const orderForm = useForm({
    name: "",
    email: prefillEmail,
    phone: "",
    notes: "",
    items: [],
});

const preorderForm = useForm({
    item_id: "",
    name: "",
    email: prefillEmail,
    quantity: 1,
    notes: "",
});

watch(
    availableItems,
    (items) => {
        for (const item of items) {
            if (typeof addQuantities[item.id] === "undefined") {
                addQuantities[item.id] = 1;
            }

            if (item.requires_size && typeof addSelections[item.id] === "undefined") {
                addSelections[item.id] = "";
            }
        }
    },
    { immediate: true }
);

watch(
    preorderItems,
    (items) => {
        if (!preorderForm.item_id && items.length > 0) {
            preorderForm.item_id = items[0].id;
        }
    },
    { immediate: true }
);

const hasCartItems = computed(() => orderForm.items.length > 0);

const cartItemCount = computed(() =>
    orderForm.items.reduce((sum, item) => sum + Number(item.quantity || 0), 0)
);

const cartTotalCents = computed(() =>
    orderForm.items.reduce((sum, item) => sum + Number(item.price_cents || 0) * Number(item.quantity || 0), 0)
);

const cartErrors = computed(() =>
    Object.entries(orderForm.errors)
        .filter(([key]) => key.startsWith("items"))
        .map(([, value]) => value)
);

const formatCurrency = (cents) =>
    new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "USD",
    }).format((Number(cents) || 0) / 100);

const normalizeQuantity = (value) => {
    const parsed = Number(value ?? 0);
    if (!Number.isFinite(parsed) || parsed < 1) {
        return 1;
    }

    return Math.min(99, Math.floor(parsed));
};

const addItemToCart = (item) => {
    delete addErrors[item.id];
    orderForm.clearErrors();

    const quantity = normalizeQuantity(addQuantities[item.id]);
    const size = (addSelections[item.id] ?? "").trim();

    if (item.requires_size && !size) {
        addErrors[item.id] = `Choose a size for ${item.name}.`;
        return;
    }

    const existing = orderForm.items.find((entry) => entry.id === item.id && (entry.size ?? "") === size);

    if (existing) {
        existing.quantity = Math.min(99, normalizeQuantity(existing.quantity) + quantity);
        return;
    }

    orderForm.items.push({
        id: item.id,
        name: item.name,
        quantity,
        size: size || null,
        size_options: item.size_options ?? [],
        price_cents: item.price_cents ?? 0,
        price_display: item.price_display ?? formatCurrency(item.price_cents ?? 0),
        requires_size: item.requires_size ?? false,
    });
};

const removeFromCart = (index) => {
    orderForm.items.splice(index, 1);
};

const submitOrder = () => {
    if (!hasCartItems.value) {
        orderForm.setError("items", "Add at least one item to your cart before submitting.");
        return;
    }

    orderForm.transform((data) => ({
        ...data,
        items: data.items.map((item) => ({
            id: item.id,
            quantity: normalizeQuantity(item.quantity),
            size: item.size ?? "",
        })),
    }));

    orderForm.post(route("merchandise.order"), {
        preserveScroll: true,
        onSuccess: () => {
            orderForm.clearErrors();
            orderForm.reset("notes");
            orderForm.items = [];
        },
        onFinish: () => {
            orderForm.transform((data) => data);
        },
    });
};

const submitPreorder = () => {
    preorderForm.post(route("merchandise.preorder"), {
        preserveScroll: true,
        onSuccess: () => {
            preorderForm.reset("quantity", "notes");
            preorderForm.quantity = 1;
        },
    });
};
</script>

<template>
    <AppLayout title="Merchandise">
        <template #header>
            <h2 class="font-semibold text-xl leading-tight">
                Lodge Merchandise
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div
                    v-if="flashSuccess"
                    class="p-3 rounded-md border border-green-200 bg-green-50 text-green-900 dark:border-green-900/40 dark:bg-green-950/30 dark:text-green-100"
                >
                    {{ flashSuccess }}
                </div>

                <div class="bg-surface-0 shadow-xl sm:rounded-lg p-6 dark:bg-surface-900 dark:text-surface-100">
                    <h3 class="text-lg font-semibold">How Orders Work</h3>
                    <p class="mt-2 text-sm opacity-90">
                        This page collects order interest only. No payment information is accepted on the website.
                        We will follow up directly to coordinate fulfillment.
                    </p>
                    <p v-if="prefillEmail" class="mt-1 text-sm opacity-80">
                        Your email was pre-filled from your account and can be edited before submitting.
                    </p>
                </div>

                <div class="bg-surface-0 shadow-xl sm:rounded-lg p-6 dark:bg-surface-900 dark:text-surface-100">
                    <h3 class="text-lg font-semibold">Available Merchandise</h3>
                    <p class="mt-2 text-sm opacity-90">Add currently available items to your cart and submit an order request.</p>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <div
                            v-for="item in availableItems"
                            :key="item.id"
                            class="rounded-lg border border-surface-200 dark:border-surface-700 p-4 bg-surface-50 dark:bg-surface-800"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <h4 class="font-semibold">{{ item.name }}</h4>
                                <span class="font-semibold">{{ item.price_display }}</span>
                            </div>
                            <p class="text-sm mt-2 opacity-90">{{ item.description }}</p>

                            <p
                                v-if="item.is_limited_edition && item.stock_remaining !== null"
                                class="text-xs mt-2 font-medium text-amber-700 dark:text-amber-300"
                            >
                                Limited edition: {{ item.stock_remaining }} remaining
                            </p>

                            <div class="mt-4 space-y-2">
                                <div v-if="item.requires_size">
                                    <label class="text-sm font-medium">Size</label>
                                    <Select
                                        v-model="addSelections[item.id]"
                                        :options="item.size_options"
                                        placeholder="Select size"
                                        class="w-full mt-1"
                                    />
                                </div>

                                <div>
                                    <label class="text-sm font-medium">Quantity</label>
                                    <InputNumber
                                        v-model="addQuantities[item.id]"
                                        :min="1"
                                        :max="99"
                                        :minFractionDigits="0"
                                        :maxFractionDigits="0"
                                        showButtons
                                        class="w-full mt-1"
                                    />
                                </div>
                            </div>

                            <small v-if="addErrors[item.id]" class="block mt-2 text-red-600 dark:text-red-400">
                                {{ addErrors[item.id] }}
                            </small>

                            <Button
                                class="mt-4 w-full"
                                label="Add to cart"
                                @click="addItemToCart(item)"
                                type="button"
                            />
                        </div>
                    </div>
                </div>

                <div class="bg-surface-0 shadow-xl sm:rounded-lg p-6 dark:bg-surface-900 dark:text-surface-100">
                    <h3 class="text-lg font-semibold">Order Request Cart</h3>
                    <p class="mt-2 text-sm opacity-90">Items: {{ cartItemCount }} | Estimated total: {{ formatCurrency(cartTotalCents) }}</p>

                    <div v-if="!hasCartItems" class="mt-4 rounded border border-dashed border-surface-300 dark:border-surface-600 p-4 text-sm opacity-90">
                        Your cart is empty.
                    </div>

                    <div v-else class="mt-4 space-y-3">
                        <div
                            v-for="(item, index) in orderForm.items"
                            :key="`${item.id}-${index}`"
                            class="rounded border border-surface-200 dark:border-surface-700 p-4 bg-surface-50 dark:bg-surface-800"
                        >
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                                <div>
                                    <div class="font-medium">{{ item.name }}</div>
                                    <div class="text-sm opacity-80">{{ item.price_display }} each</div>
                                </div>
                                <div class="font-medium">{{ formatCurrency(item.price_cents * item.quantity) }}</div>
                            </div>

                            <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div>
                                    <label class="text-sm font-medium">Quantity</label>
                                    <InputNumber
                                        v-model="item.quantity"
                                        :min="1"
                                        :max="99"
                                        :minFractionDigits="0"
                                        :maxFractionDigits="0"
                                        showButtons
                                        class="w-full mt-1"
                                    />
                                </div>

                                <div v-if="item.requires_size">
                                    <label class="text-sm font-medium">Size</label>
                                    <Select
                                        v-model="item.size"
                                        :options="item.size_options"
                                        placeholder="Select size"
                                        class="w-full mt-1"
                                    />
                                </div>

                                <div class="flex items-end">
                                    <Button
                                        label="Remove"
                                        severity="danger"
                                        outlined
                                        class="w-full md:w-auto"
                                        type="button"
                                        @click="removeFromCart(index)"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="orderForm.errors.items" class="mt-3 text-red-600 dark:text-red-400 text-sm">
                        {{ orderForm.errors.items }}
                    </div>

                    <div v-if="cartErrors.length > 0" class="mt-3 text-red-600 dark:text-red-400 text-sm space-y-1">
                        <div v-for="(error, index) in cartErrors" :key="`cart-error-${index}`">{{ error }}</div>
                    </div>

                    <form class="mt-6 space-y-4" @submit.prevent="submitOrder">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex flex-col">
                                <label for="order_name" class="font-medium">Name</label>
                                <InputText id="order_name" v-model="orderForm.name" class="mt-1 w-full" autocomplete="name" />
                                <small v-if="orderForm.errors.name" class="mt-1 text-red-600 dark:text-red-400">{{ orderForm.errors.name }}</small>
                            </div>
                            <div class="flex flex-col">
                                <label for="order_email" class="font-medium">Email *</label>
                                <InputText id="order_email" v-model="orderForm.email" type="email" class="mt-1 w-full" autocomplete="email" required />
                                <small v-if="orderForm.errors.email" class="mt-1 text-red-600 dark:text-red-400">{{ orderForm.errors.email }}</small>
                            </div>
                            <div class="flex flex-col">
                                <label for="order_phone" class="font-medium">Phone</label>
                                <InputText id="order_phone" v-model="orderForm.phone" class="mt-1 w-full" autocomplete="tel" />
                                <small v-if="orderForm.errors.phone" class="mt-1 text-red-600 dark:text-red-400">{{ orderForm.errors.phone }}</small>
                            </div>
                            <div class="flex flex-col">
                                <label for="order_notes" class="font-medium">Notes</label>
                                <Textarea id="order_notes" v-model="orderForm.notes" rows="3" autoResize class="mt-1 w-full" />
                                <small v-if="orderForm.errors.notes" class="mt-1 text-red-600 dark:text-red-400">{{ orderForm.errors.notes }}</small>
                            </div>
                        </div>

                        <Button
                            type="submit"
                            label="Submit order request"
                            class="w-full md:w-auto"
                            :loading="orderForm.processing"
                        />
                    </form>
                </div>

                <div class="bg-surface-0 shadow-xl sm:rounded-lg p-6 dark:bg-surface-900 dark:text-surface-100">
                    <h3 class="text-lg font-semibold">Pre-order Interest</h3>
                    <p class="mt-2 text-sm opacity-90">Tell us what you would buy so we can plan future runs.</p>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div
                            v-for="item in preorderItems"
                            :key="`preorder-${item.id}`"
                            class="rounded border border-surface-200 dark:border-surface-700 p-4 bg-surface-50 dark:bg-surface-800"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <h4 class="font-semibold">{{ item.name }}</h4>
                                <span class="font-semibold">{{ item.price_display }}</span>
                            </div>
                            <p class="text-sm mt-2 opacity-90">{{ item.description }}</p>
                        </div>
                    </div>

                    <form class="mt-6 space-y-4" @submit.prevent="submitPreorder">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex flex-col">
                                <label for="preorder_item" class="font-medium">Pre-order Item *</label>
                                <Select
                                    id="preorder_item"
                                    v-model="preorderForm.item_id"
                                    :options="preorderItems"
                                    optionLabel="name"
                                    optionValue="id"
                                    placeholder="Select an item"
                                    class="mt-1 w-full"
                                />
                                <small v-if="preorderForm.errors.item_id" class="mt-1 text-red-600 dark:text-red-400">{{ preorderForm.errors.item_id }}</small>
                            </div>
                            <div class="flex flex-col">
                                <label for="preorder_qty" class="font-medium">Quantity Interested *</label>
                                <InputNumber
                                    id="preorder_qty"
                                    v-model="preorderForm.quantity"
                                    :min="1"
                                    :max="99"
                                    :minFractionDigits="0"
                                    :maxFractionDigits="0"
                                    showButtons
                                    class="mt-1 w-full"
                                />
                                <small v-if="preorderForm.errors.quantity" class="mt-1 text-red-600 dark:text-red-400">{{ preorderForm.errors.quantity }}</small>
                            </div>
                            <div class="flex flex-col">
                                <label for="preorder_name" class="font-medium">Name</label>
                                <InputText id="preorder_name" v-model="preorderForm.name" class="mt-1 w-full" autocomplete="name" />
                                <small v-if="preorderForm.errors.name" class="mt-1 text-red-600 dark:text-red-400">{{ preorderForm.errors.name }}</small>
                            </div>
                            <div class="flex flex-col">
                                <label for="preorder_email" class="font-medium">Email *</label>
                                <InputText id="preorder_email" v-model="preorderForm.email" type="email" class="mt-1 w-full" autocomplete="email" required />
                                <small v-if="preorderForm.errors.email" class="mt-1 text-red-600 dark:text-red-400">{{ preorderForm.errors.email }}</small>
                            </div>
                            <div class="flex flex-col md:col-span-2">
                                <label for="preorder_notes" class="font-medium">Notes</label>
                                <Textarea id="preorder_notes" v-model="preorderForm.notes" rows="3" autoResize class="mt-1 w-full" />
                                <small v-if="preorderForm.errors.notes" class="mt-1 text-red-600 dark:text-red-400">{{ preorderForm.errors.notes }}</small>
                            </div>
                        </div>

                        <Button
                            type="submit"
                            label="Submit pre-order interest"
                            class="w-full md:w-auto"
                            :loading="preorderForm.processing"
                        />
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
