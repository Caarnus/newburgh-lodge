<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';

const props = defineProps({
    visible: { type: Boolean, default: false },
    person: { type: Object, default: null },
    fromSection: { type: String, default: null },
});

const emit = defineEmits(['update:visible', 'saved']);

const contactTypeOptions = [
    { label: 'Call', value: 'call' },
    { label: 'Text', value: 'text' },
    { label: 'Email', value: 'email' },
    { label: 'Visit', value: 'visit' },
    { label: 'Other', value: 'other' },
];

const nowLocal = () => {
    const date = new Date();
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');

    return `${year}-${month}-${day}T${hours}:${minutes}`;
};

const form = useForm({
    contacted_at: nowLocal(),
    contact_type: null,
    notes: '',
    from: props.fromSection,
});

const personName = computed(() => props.person?.display_name ?? 'person');

const close = () => {
    emit('update:visible', false);
};

const resetForm = () => {
    form.reset();
    form.clearErrors();
    form.contacted_at = nowLocal();
    form.contact_type = null;
    form.notes = '';
    form.from = props.fromSection;
};

watch(() => props.visible, (visible) => {
    if (visible) {
        resetForm();
    }
});

watch(() => props.fromSection, (value) => {
    form.from = value;
});

const submit = (logNow = false) => {
    if (!props.person?.id) {
        return;
    }

    form.from = props.fromSection;

    if (logNow) {
        form.contacted_at = '';
        form.contact_type = null;
        form.notes = '';
    }

    form.post(route('manage.member-directory.people.contact-logs.store', { person: props.person.id }), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            emit('saved');
            close();
        },
    });
};
</script>

<template>
    <Dialog
        :visible="visible"
        modal
        class="w-full sm:w-[34rem]"
        header="Quick Contact Log"
        @update:visible="emit('update:visible', $event)"
        @hide="close"
    >
        <div class="space-y-4">
            <div class="rounded-lg border border-surface-200 bg-surface-50 p-3 text-sm dark:border-surface-700 dark:bg-surface-800">
                Logging contact for <span class="font-medium">{{ personName }}</span>.
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium">Contacted At</label>
                <InputText
                    v-model="form.contacted_at"
                    type="datetime-local"
                    class="w-full"
                />
                <p v-if="form.errors.contacted_at" class="mt-1 text-sm text-red-500">
                    {{ form.errors.contacted_at }}
                </p>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium">Contact Type</label>
                <Select
                    v-model="form.contact_type"
                    :options="contactTypeOptions"
                    option-label="label"
                    option-value="value"
                    class="w-full"
                    show-clear
                    placeholder="Optional"
                />
                <p v-if="form.errors.contact_type" class="mt-1 text-sm text-red-500">
                    {{ form.errors.contact_type }}
                </p>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium">Notes</label>
                <Textarea v-model="form.notes" rows="4" class="w-full" />
                <p v-if="form.errors.notes" class="mt-1 text-sm text-red-500">
                    {{ form.errors.notes }}
                </p>
            </div>
        </div>

        <template #footer>
            <Button
                label="Cancel"
                severity="secondary"
                text
                @click="close"
            />
            <Button
                label="Log Contact Now"
                severity="secondary"
                outlined
                :loading="form.processing"
                @click="submit(true)"
            />
            <Button
                label="Save Log"
                :loading="form.processing"
                @click="submit(false)"
            />
        </template>
    </Dialog>
</template>
