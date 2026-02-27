<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { computed, ref } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";

import InputText from "primevue/inputtext";
import InputMask from "primevue/inputmask";
import InputNumber from "primevue/inputnumber";
import Dropdown from "primevue/dropdown";
import Calendar from "primevue/calendar";
import Checkbox from "primevue/checkbox";
import Textarea from "primevue/textarea";
import FileUpload from "primevue/fileupload";
import Button from "primevue/button";
import Divider from "primevue/divider";

const page = usePage();
const loading = ref(false);

const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

// If you pass these from the controller, we’ll use them; otherwise fall back to defaults.
const residencyDurationOptions = computed(
    () =>
        page.props.residencyDurationOptions ?? [
            { label: "Less than 1 year", value: "Less than 1 year" },
            { label: "1–3 years", value: "1-3 years" },
            { label: "3–5 years", value: "3-5 years" },
            { label: "5+ years", value: "5+ years" },
        ]
);

const educationLevelOptions = computed(
    () =>
        page.props.educationLevelOptions ?? [
            { label: "High School", value: "High School" },
            { label: "College/University", value: "College/University" },
            { label: "Trade/Technical Program", value: "Trade/Technical Program" },
            { label: "Other", value: "Other" },
        ]
);

const lodgeRelationshipOptions = computed(
    () =>
        page.props.lodgeRelationshipOptions ?? [
            { label: "None", value: "none" },
            { label: "Family of a member", value: "family" },
            { label: "Friend of a member", value: "friend" },
            { label: "Involved with Lodge events/partners", value: "events" },
            { label: "Other", value: "other" },
        ]
);

const gpaScaleOptions = computed(
    () =>
        page.props.gpaScaleOptions ?? [
            { label: "4.0 scale", value: "4.0" },
            { label: "5.0 scale", value: "5.0" },
            { label: "100 point", value: "100" },
            { label: "Other", value: "Other" },
        ]
);

const maxReasonChars = computed(() => page.props.maxReasonChars ?? 1000);

// Calendar uses Date objects; backend expects a date string.
const expectedGraduationDate = ref(null);
const formatDateYYYYMMDD = (d) => {
    if (!d) return null;
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, "0");
    const dd = String(d.getDate()).padStart(2, "0");
    return `${yyyy}-${mm}-${dd}`;
};

const form = useForm({
    // Applicant
    first_name: "",
    last_name: "",
    email: "",
    phone: "",
    siblings: 0,

    // Residency
    is_warrick_resident: false,
    address1: "",
    address2: "",
    city: "",
    state: "IN",
    zip: "",
    residency_duration: "",

    // Education
    current_school: "",
    education_level: "",
    current_year: "",
    expected_graduation: null, // will set on submit
    planned_program: "",
    gpa: "",
    gpa_scale: "",

    // Activities / awards
    activities: "",
    awards: "",

    // Reason (max 1000 in your validator)
    reason: "",

    // Lodge relationship
    lodge_relationship: "",
    lodge_relationship_detail: "",

    // Uploads
    attachments: [],

    // Anti-bot (per your validator)
    hp_field: "",
    started_at: Date.now(),
});

const reasonCount = computed(() => (form.reason ?? "").length);
const awardsCount = computed(() => (form.awards ?? "").length);
const activitiesCount = computed(() => (form.activities ?? "").length);
const reasonError = computed(() => form.errors.reason);

const onFileSelect = (event) => {
    form.attachments = event.files ?? [];
};

const onFileClear = () => {
    form.attachments = [];
};

const submitForm = () => {
    loading.value = true;

    form.expected_graduation = formatDateYYYYMMDD(expectedGraduationDate.value);

    form.post(route("scholarship.apply.store"), {
        preserveScroll: true,
        forceFormData: true, // needed for file uploads
        onFinish: () => {
            loading.value = false;
        },
    });
};
</script>

<template>
    <AppLayout title="Scholarship Application">
        <template #header>
            <h2 class="font-semibold text-xl leading-tight">Scholarship Application</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-surface-0 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="dark:bg-surface-900 dark:text-surface-200 font-sans">
                        <div class="max-w-4xl mx-auto p-6">
                            <div
                                v-if="flashSuccess"
                                class="mb-4 p-3 rounded-md border border-green-200 bg-green-50 text-green-900 dark:border-green-900/40 dark:bg-green-950/30 dark:text-green-100"
                            >
                                {{ flashSuccess }}
                            </div>

                            <div
                                v-if="flashError"
                                class="mb-4 p-3 rounded-md border border-red-200 bg-red-50 text-red-900 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-100"
                            >
                                {{ flashError }}
                            </div>

                            <form @submit.prevent="submitForm" class="space-y-6">
                                <!-- Honeypot -->
                                <div style="position:absolute; left:-10000px; top:auto; width:1px; height:1px; overflow:hidden;">
                                    <label>Leave this field blank</label>
                                    <input v-model="form.hp_field" type="text" autocomplete="off" />
                                </div>

                                <Divider align="left"><b>Eligibility</b></Divider>

                                <div class="flex items-start gap-3">
                                    <Checkbox v-model="form.is_warrick_resident" inputId="is_warrick_resident" binary />
                                    <label for="is_warrick_resident" class="text-surface-700 dark:text-surface-300">
                                        I am a resident of Warrick County, Indiana.
                                    </label>
                                </div>
                                <small v-if="form.errors.is_warrick_resident" class="text-red-600 dark:text-red-400">
                                    {{ form.errors.is_warrick_resident }}
                                </small>

                                <Divider align="left"><b>Applicant Information</b></Divider>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="flex flex-col">
                                        <label for="first_name" class="font-medium text-surface-700 dark:text-surface-300">First Name *</label>
                                        <InputText id="first_name" v-model="form.first_name" class="mt-1 w-full" autocomplete="given-name" />
                                        <small v-if="form.errors.first_name" class="mt-1 text-red-600 dark:text-red-400">{{ form.errors.first_name }}</small>
                                    </div>

                                    <div class="flex flex-col">
                                        <label for="last_name" class="font-medium text-surface-700 dark:text-surface-300">Last Name *</label>
                                        <InputText id="last_name" v-model="form.last_name" class="mt-1 w-full" autocomplete="family-name" />
                                        <small v-if="form.errors.last_name" class="mt-1 text-red-600 dark:text-red-400">{{ form.errors.last_name }}</small>
                                    </div>

                                    <div class="flex flex-col">
                                        <label for="email" class="font-medium text-surface-700 dark:text-surface-300">Email *</label>
                                        <InputText id="email" v-model="form.email" type="email" class="mt-1 w-full" autocomplete="email" />
                                        <small v-if="form.errors.email" class="mt-1 text-red-600 dark:text-red-400">{{ form.errors.email }}</small>
                                    </div>

                                    <div class="flex flex-col">
                                        <label for="phone" class="font-medium text-surface-700 dark:text-surface-300">Phone</label>
                                        <InputMask
                                            id="phone"
                                            v-model="form.phone"
                                            mask="(999) 999-9999"
                                            placeholder="(555) 555-5555"
                                            class="mt-1 w-full"
                                            autocomplete="tel"
                                        />
                                        <small v-if="form.errors.phone" class="mt-1 text-red-600 dark:text-red-400">{{ form.errors.phone }}</small>
                                    </div>

                                    <div class="flex flex-col">
                                        <label for="siblings" class="font-medium text-surface-700 dark:text-surface-300">Number of brothers or sisters in household excluding applicant *</label>
                                        <InputNumber
                                            id="siblings"
                                            v-model="form.siblings"
                                            class="mt-1 w-full"
                                            :min="0"
                                            :max="50"
                                            showButtons
                                        />
                                        <small v-if="form.errors.siblings" class="mt-1 text-red-600 dark:text-red-400">{{ form.errors.siblings }}</small>
                                    </div>
                                </div>

                                <Divider align="left"><b>Address</b></Divider>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="flex flex-col md:col-span-2">
                                        <label for="address1" class="font-medium text-surface-700 dark:text-surface-300">Address Line 1 *</label>
                                        <InputText id="address1" v-model="form.address1" class="mt-1 w-full" autocomplete="address-line1" />
                                        <small v-if="form.errors.address1" class="mt-1 text-red-600 dark:text-red-400">{{ form.errors.address1 }}</small>
                                    </div>

                                    <div class="flex flex-col md:col-span-2">
                                        <label for="address2" class="font-medium text-surface-700 dark:text-surface-300">Address Line 2</label>
                                        <InputText id="address2" v-model="form.address2" class="mt-1 w-full" autocomplete="address-line2" />
                                        <small v-if="form.errors.address2" class="mt-1 text-red-600 dark:text-red-400">{{ form.errors.address2 }}</small>
                                    </div>

                                    <div class="flex flex-col">
                                        <label for="city" class="font-medium text-surface-700 dark:text-surface-300">City *</label>
                                        <InputText id="city" v-model="form.city" class="mt-1 w-full" autocomplete="address-level2" />
                                        <small v-if="form.errors.city" class="mt-1 text-red-600 dark:text-red-400">{{ form.errors.city }}</small>
                                    </div>

                                    <div class="flex flex-col">
                                        <label for="state" class="font-medium text-surface-700 dark:text-surface-300">State *</label>
                                        <InputText id="state" v-model="form.state" class="mt-1 w-full" maxlength="2" autocomplete="address-level1" />
                                        <small v-if="form.errors.state" class="mt-1 text-red-600 dark:text-red-400">{{ form.errors.state }}</small>
                                    </div>

                                    <div class="flex flex-col">
                                        <label for="zip" class="font-medium text-surface-700 dark:text-surface-300">ZIP *</label>
                                        <InputText id="zip" v-model="form.zip" class="mt-1 w-full" autocomplete="postal-code" />
                                        <small v-if="form.errors.zip" class="mt-1 text-red-600 dark:text-red-400">{{ form.errors.zip }}</small>
                                    </div>

                                    <div class="flex flex-col">
                                        <label for="residency_duration" class="font-medium text-surface-700 dark:text-surface-300">Residency Duration</label>
                                        <Dropdown
                                            id="residency_duration"
                                            v-model="form.residency_duration"
                                            :options="residencyDurationOptions"
                                            optionLabel="label"
                                            optionValue="value"
                                            placeholder="Select"
                                            class="mt-1 w-full"
                                        />
                                        <small v-if="form.errors.residency_duration" class="mt-1 text-red-600 dark:text-red-400">{{ form.errors.residency_duration }}</small>
                                    </div>
                                </div>

                                <Divider align="left"><b>Education</b></Divider>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="flex flex-col md:col-span-2">
                                        <label for="current_school" class="font-medium text-surface-700 dark:text-surface-300">Current School</label>
                                        <InputText id="current_school" v-model="form.current_school" class="mt-1 w-full" />
                                        <small v-if="form.errors.current_school" class="mt-1 text-red-600 dark:text-red-400">{{ form.errors.current_school }}</small>
                                    </div>

                                    <div class="flex flex-col">
                                        <label for="education_level" class="font-medium text-surface-700 dark:text-surface-300">Education Level</label>
                                        <Dropdown
                                            id="education_level"
                                            v-model="form.education_level"
                                            :options="educationLevelOptions"
                                            optionLabel="label"
                                            optionValue="value"
                                            placeholder="Select"
                                            class="mt-1 w-full"
                                        />
                                        <small v-if="form.errors.education_level" class="mt-1 text-red-600 dark:text-red-400">{{ form.errors.education_level }}</small>
                                    </div>

                                    <div class="flex flex-col">
                                        <label for="current_year" class="font-medium text-surface-700 dark:text-surface-300">Current Year</label>
                                        <InputText id="current_year" v-model="form.current_year" class="mt-1 w-full" placeholder="e.g., Senior, Freshman, 2nd year" />
                                        <small v-if="form.errors.current_year" class="mt-1 text-red-600 dark:text-red-400">{{ form.errors.current_year }}</small>
                                    </div>

                                    <div class="flex flex-col">
                                        <label for="expected_graduation" class="font-medium text-surface-700 dark:text-surface-300">Expected Graduation</label>
                                        <Calendar
                                            id="expected_graduation"
                                            v-model="expectedGraduationDate"
                                            class="mt-1 w-full"
                                            showIcon
                                            dateFormat="mm/dd/yy"
                                        />
                                        <small v-if="form.errors.expected_graduation" class="mt-1 text-red-600 dark:text-red-400">{{ form.errors.expected_graduation }}</small>
                                    </div>

                                    <div class="flex flex-col md:col-span-2">
                                        <label for="planned_program" class="font-medium text-surface-700 dark:text-surface-300">Planned Program / Major</label>
                                        <InputText id="planned_program" v-model="form.planned_program" class="mt-1 w-full" />
                                        <small v-if="form.errors.planned_program" class="mt-1 text-red-600 dark:text-red-400">{{ form.errors.planned_program }}</small>
                                    </div>

                                    <div class="flex flex-col">
                                        <label for="gpa" class="font-medium text-surface-700 dark:text-surface-300">GPA</label>
                                        <InputText id="gpa" v-model="form.gpa" class="mt-1 w-full" placeholder="e.g., 3.7" />
                                        <small v-if="form.errors.gpa" class="mt-1 text-red-600 dark:text-red-400">{{ form.errors.gpa }}</small>
                                    </div>

                                    <div class="flex flex-col">
                                        <label for="gpa_scale" class="font-medium text-surface-700 dark:text-surface-300">GPA Scale</label>
                                        <Dropdown
                                            id="gpa_scale"
                                            v-model="form.gpa_scale"
                                            :options="gpaScaleOptions"
                                            optionLabel="label"
                                            optionValue="value"
                                            placeholder="Select"
                                            class="mt-1 w-full"
                                        />
                                        <small v-if="form.errors.gpa_scale" class="mt-1 text-red-600 dark:text-red-400">{{ form.errors.gpa_scale }}</small>
                                    </div>
                                </div>

                                <Divider align="left"><b>Activities & Awards</b></Divider>

                                <div class="grid grid-cols-1 gap-4">
                                    <div class="flex flex-col">
                                        <label for="activities" class="font-medium text-surface-700 dark:text-surface-300">Activities / Community Involvement</label>
                                        <Textarea id="activities" v-model="form.activities" :maxlength="maxReasonChars" rows="5" class="mt-1 w-full" autoResize />
                                        <div class="mt-1 flex justify-between text-sm opacity-70">
                                            <span>{{ activitiesCount }}/{{ maxReasonChars }}</span>
                                        </div>
                                        <small v-if="form.errors.activities" class="mt-1 text-red-600 dark:text-red-400">{{ form.errors.activities }}</small>
                                    </div>

                                    <div class="flex flex-col">
                                        <label for="awards" class="font-medium text-surface-700 dark:text-surface-300">Awards / Recognitions</label>
                                        <Textarea id="awards" v-model="form.awards" :maxlength="maxReasonChars" rows="4" class="mt-1 w-full" autoResize />
                                        <div class="mt-1 flex justify-between text-sm opacity-70">
                                            <span>{{ awardsCount }}/{{ maxReasonChars }}</span>
                                        </div>
                                        <small v-if="form.errors.awards" class="mt-1 text-red-600 dark:text-red-400">{{ form.errors.awards }}</small>
                                    </div>
                                </div>

                                <Divider align="left"><b>Why are you applying?</b></Divider>

                                <div class="flex flex-col">
                                    <label for="reason" class="font-medium text-surface-700 dark:text-surface-300">
                                        In {{ maxReasonChars }} characters or fewer, tell us why you’re applying and what this scholarship would help you achieve.
                                    </label>
                                    <Textarea
                                        id="reason"
                                        v-model="form.reason"
                                        :maxlength="maxReasonChars"
                                        rows="5"
                                        class="mt-1 w-full"
                                        autoResize
                                    />
                                    <div class="mt-1 flex justify-between text-sm opacity-70">
                                        <span>{{ reasonCount }}/{{ maxReasonChars }}</span>
                                    </div>
                                    <small v-if="reasonError" class="mt-1 text-red-600 dark:text-red-400">{{ reasonError }}</small>
                                </div>

                                <Divider align="left"><b>Relationship to Newburgh Lodge</b></Divider>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="flex flex-col">
                                        <label for="lodge_relationship" class="font-medium text-surface-700 dark:text-surface-300">Relationship</label>
                                        <Dropdown
                                            id="lodge_relationship"
                                            v-model="form.lodge_relationship"
                                            :options="lodgeRelationshipOptions"
                                            optionLabel="label"
                                            optionValue="value"
                                            placeholder="Select"
                                            class="mt-1 w-full"
                                        />
                                        <small v-if="form.errors.lodge_relationship" class="mt-1 text-red-600 dark:text-red-400">{{ form.errors.lodge_relationship }}</small>
                                    </div>

                                    <div class="flex flex-col">
                                        <label for="lodge_relationship_detail" class="font-medium text-surface-700 dark:text-surface-300">Details (optional)</label>
                                        <InputText
                                            id="lodge_relationship_detail"
                                            v-model="form.lodge_relationship_detail"
                                            class="mt-1 w-full"
                                            placeholder="Member name / relationship / how you’re connected"
                                        />
                                        <small v-if="form.errors.lodge_relationship_detail" class="mt-1 text-red-600 dark:text-red-400">
                                            {{ form.errors.lodge_relationship_detail }}
                                        </small>
                                    </div>
                                </div>

                                <Divider align="left"><b>File Uploads</b></Divider>

                                <label for="attachments" class="font-medium text-surface-700 dark:text-surface-300">
                                    Please attach a copy of your transcript. You may also upload a copy of your resume, a letter of recommendation, or any other relevant documents up to 3 total.
                                </label>
                                <div class="flex flex-col">
                                    <FileUpload
                                        name="attachments[]"
                                        mode="advanced"
                                        :multiple="true"
                                        :fileLimit="3"
                                        :maxFileSize="5120000"
                                        accept=".pdf,.jpg,.jpeg,.png"
                                        chooseLabel="Choose files"
                                        uploadLabel=" "
                                        cancelLabel="Clear"
                                        :auto="false"
                                        :customUpload="true"
                                        @select="onFileSelect"
                                        @clear="onFileClear"
                                        class="mt-1 w-full"
                                    />
                                    <div class="text-sm opacity-70 mt-1">
                                        Up to 3 files. PDF/JPG/PNG. Max 5MB each.
                                    </div>
                                    <small v-if="form.errors.attachments" class="mt-1 text-red-600 dark:text-red-400">{{ form.errors.attachments }}</small>
                                    <small v-if="form.errors['attachments.0']" class="mt-1 text-red-600 dark:text-red-400">{{ form.errors['attachments.0'] }}</small>
                                    <small v-if="form.errors['attachments.1']" class="mt-1 text-red-600 dark:text-red-400">{{ form.errors['attachments.1'] }}</small>
                                    <small v-if="form.errors['attachments.2']" class="mt-1 text-red-600 dark:text-red-400">{{ form.errors['attachments.2'] }}</small>
                                </div>

                                <Button
                                    label="Submit Application"
                                    class="w-full bg-primary-600 hover:bg-primary-700 text-surface-0 dark:text-surface-950 font-medium py-2 px-4 rounded-md"
                                    :loading="loading || form.processing"
                                    type="submit"
                                />
                            </form>

                            <div class="text-xs opacity-70 mt-6">
                                After submitting, you’ll receive an email with a verification link. Your application is not considered submitted until verified.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
