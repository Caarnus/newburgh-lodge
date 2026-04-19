<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { computed, onMounted, reactive, watch } from "vue";
import { Link, useForm, usePage } from "@inertiajs/vue3";
import { useMerchandiseCart } from "@/Composables/useMerchandiseCart";

import InputText from "primevue/inputtext";
import InputNumber from "primevue/inputnumber";
import Textarea from "primevue/textarea";
import Select from "primevue/select";
import Button from "primevue/button";
import Tag from "primevue/tag";

const page = usePage();
const prefillEmail = String(page.props.prefillEmail ?? page.props.auth?.user?.email ?? "");
const flashSuccess = computed(() => page.props.flash?.success);
const activeItems = computed(() => page.props.items ?? []);

const { cartItems, hydrate, syncWithCatalogItems, updateQuantity, updateSize, removeAt, clear } = useMerchandiseCart();

const checkoutLineErrors = reactive({});

onMounted(() => {
    hydrate();
    syncWithCatalogItems(activeItems.value);
});

watch(
    activeItems,
    (rows) => {
        syncWithCatalogItems(rows);
    },
    { immediate: true, deep: true }
);

const checkoutForm = useForm({
    name: "",
    email: prefillEmail,
    phone: "",
    notes: "",
    items: [],
});

const hasCartItems = computed(() => cartItems.value.length > 0);

const cartItemCount = computed(() =>
    cartItems.value.reduce((sum, item) => sum + Number(item.quantity || 0), 0)
);

const cartTotalCents = computed(() =>
    cartItems.value.reduce((sum, item) => sum + Number(item.price_cents || 0) * Number(item.quantity || 0), 0)
);

const cartErrors = computed(() =>
    Object.entries(checkoutForm.errors)
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

const setLineQuantity = (index, value) => {
    updateQuantity(index, value);
};

const setLineSize = (index, value) => {
    updateSize(index, value);
    delete checkoutLineErrors[index];
};

const removeFromCart = (index) => {
    removeAt(index);
    delete checkoutLineErrors[index];
};

const validateLocalCart = () => {
    let isValid = true;
    for (const key of Object.keys(checkoutLineErrors)) {
        delete checkoutLineErrors[key];
    }

    cartItems.value.forEach((item, index) => {
        if (item.requires_size && !String(item.size ?? "").trim()) {
            checkoutLineErrors[index] = `Please select a size for ${item.name}.`;
            isValid = false;
        }
    });

    return isValid;
};

const submitCheckout = () => {
    checkoutForm.clearErrors();

    if (!hasCartItems.value) {
        checkoutForm.setError("items", "Add at least one item before submitting checkout.");
        return;
    }

    if (!validateLocalCart()) {
        return;
    }

    checkoutForm.transform((data) => ({
        ...data,
        items: cartItems.value.map((item) => ({
            id: item.id,
            quantity: normalizeQuantity(item.quantity),
            size: item.size ?? "",
        })),
    }));

    checkoutForm.post(route("merchandise.checkout.submit"), {
        preserveScroll: true,
        onSuccess: () => {
            clear();
            checkoutForm.clearErrors();
            checkoutForm.reset("name", "phone", "notes");
            checkoutForm.email = prefillEmail;
        },
        onFinish: () => {
            checkoutForm.transform((data) => data);
        },
    });
};
</script>

<template>
    <AppLayout title="Merchandise Checkout">
        <template #header>
            <h2 class="font-semibold text-xl leading-tight">
                Merchandise Checkout
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div
                    v-if="flashSuccess"
                    class="p-3 rounded-md border border-green-200 bg-green-50 text-green-900 dark:border-green-900/40 dark:bg-green-950/30 dark:text-green-100"
                >
                    {{ flashSuccess }}
                </div>

                <div class="bg-surface-0 shadow-xl sm:rounded-lg p-6 dark:bg-surface-900 dark:text-surface-100">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                        <h3 class="text-lg font-semibold">Review Cart</h3>
                        <p class="text-sm opacity-90">
                            {{ cartItemCount }} item(s) | Estimated total: {{ formatCurrency(cartTotalCents) }}
                        </p>
                    </div>
                    <p class="mt-2 text-sm opacity-90">
                        This checkout form collects interest and contact details only. No payment information is accepted online.
                    </p>
                </div>

                <div
                    v-if="!hasCartItems"
                    class="bg-surface-0 shadow-xl sm:rounded-lg p-6 dark:bg-surface-900 dark:text-surface-100"
                >
                    <p class="text-sm opacity-90">Your cart is empty.</p>
                    <Link class="inline-block mt-4" :href="route('merchandise.index')">
                        <Button label="Browse Merchandise" />
                    </Link>
                </div>

                <template v-else>
                    <div class="bg-surface-0 shadow-xl sm:rounded-lg p-6 dark:bg-surface-900 dark:text-surface-100 space-y-4">
                        <div
                            v-for="(item, index) in cartItems"
                            :key="`${item.id}-${index}`"
                            class="rounded border border-surface-200 dark:border-surface-700 p-4 bg-surface-50 dark:bg-surface-800"
                        >
                            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                                <div class="flex gap-3">
                                    <img
                                        v-if="item.image_url"
                                        :src="item.image_url"
                                        :alt="`${item.name} photo`"
                                        class="h-16 w-16 rounded object-cover border border-surface-200 dark:border-surface-700"
                                    />
                                    <div>
                                        <div class="font-medium">{{ item.name }}</div>
                                        <div class="text-sm opacity-80">{{ item.price_display }} each</div>
                                        <div class="mt-1">
                                            <Tag :value="item.availability_label" :severity="item.availability === 'preorder' ? 'info' : 'success'" />
                                        </div>
                                    </div>
                                </div>
                                <div class="font-medium">{{ formatCurrency(item.price_cents * item.quantity) }}</div>
                            </div>

                            <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div>
                                    <label class="text-sm font-medium">Quantity</label>
                                    <InputNumber
                                        :modelValue="item.quantity"
                                        :min="1"
                                        :max="99"
                                        :minFractionDigits="0"
                                        :maxFractionDigits="0"
                                        showButtons
                                        class="w-full mt-1"
                                        @update:modelValue="setLineQuantity(index, $event)"
                                    />
                                </div>

                                <div v-if="item.requires_size">
                                    <label class="text-sm font-medium">Size</label>
                                    <Select
                                        :modelValue="item.size"
                                        :options="item.size_options"
                                        placeholder="Select size"
                                        class="w-full mt-1"
                                        @update:modelValue="setLineSize(index, $event)"
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

                            <small v-if="checkoutLineErrors[index]" class="block mt-2 text-red-600 dark:text-red-400">
                                {{ checkoutLineErrors[index] }}
                            </small>
                        </div>
                    </div>

                    <div class="bg-surface-0 shadow-xl sm:rounded-lg p-6 dark:bg-surface-900 dark:text-surface-100">
                        <h3 class="text-lg font-semibold">Contact Information</h3>
                        <p v-if="prefillEmail" class="mt-1 text-sm opacity-80">
                            Your account email was pre-filled and can be edited.
                        </p>

                        <div v-if="checkoutForm.errors.items" class="mt-3 text-red-600 dark:text-red-400 text-sm">
                            {{ checkoutForm.errors.items }}
                        </div>

                        <div v-if="cartErrors.length > 0" class="mt-3 text-red-600 dark:text-red-400 text-sm space-y-1">
                            <div v-for="(error, index) in cartErrors" :key="`cart-error-${index}`">{{ error }}</div>
                        </div>

                        <form class="mt-4 space-y-4" @submit.prevent="submitCheckout">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex flex-col">
                                    <label for="checkout_name" class="font-medium">Name</label>
                                    <InputText id="checkout_name" v-model="checkoutForm.name" class="mt-1 w-full" autocomplete="name" />
                                    <small v-if="checkoutForm.errors.name" class="mt-1 text-red-600 dark:text-red-400">{{ checkoutForm.errors.name }}</small>
                                </div>
                                <div class="flex flex-col">
                                    <label for="checkout_email" class="font-medium">Email *</label>
                                    <InputText id="checkout_email" v-model="checkoutForm.email" type="email" class="mt-1 w-full" autocomplete="email" required />
                                    <small v-if="checkoutForm.errors.email" class="mt-1 text-red-600 dark:text-red-400">{{ checkoutForm.errors.email }}</small>
                                </div>
                                <div class="flex flex-col">
                                    <label for="checkout_phone" class="font-medium">Phone</label>
                                    <InputText id="checkout_phone" v-model="checkoutForm.phone" class="mt-1 w-full" autocomplete="tel" />
                                    <small v-if="checkoutForm.errors.phone" class="mt-1 text-red-600 dark:text-red-400">{{ checkoutForm.errors.phone }}</small>
                                </div>
                                <div class="flex flex-col">
                                    <label for="checkout_notes" class="font-medium">Notes</label>
                                    <Textarea id="checkout_notes" v-model="checkoutForm.notes" rows="3" autoResize class="mt-1 w-full" />
                                    <small v-if="checkoutForm.errors.notes" class="mt-1 text-red-600 dark:text-red-400">{{ checkoutForm.errors.notes }}</small>
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row gap-2">
                                <Link :href="route('merchandise.index')">
                                    <Button label="Back to Merchandise" severity="secondary" outlined type="button" />
                                </Link>
                                <Button
                                    type="submit"
                                    label="Submit Merchandise Request"
                                    :loading="checkoutForm.processing"
                                />
                            </div>
                        </form>
                    </div>
                </template>
            </div>
        </div>
    </AppLayout>
</template>
