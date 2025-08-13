<script setup lang="ts">
import {ref} from "vue";
import {router, usePage} from "@inertiajs/vue3";
import {useToast, Dialog, Select, InputText, ToggleSwitch, Button, Textarea, FileUpload} from "primevue";
import {route} from "ziggy-js";
import AppLayout from "@/Layouts/AppLayout.vue";

const toast = useToast();
const page = usePage();

const tiles = ref<any[]>(JSON.parse(JSON.stringify(page.props.tiles || [])));
const selected = ref<any|null>(null);
const showEdit = ref(false);

const types = [
    { label: 'Text', value: 'text' },
    { label: 'Newsletter', value: 'newsletter' },
    { label: 'Image + Text', value: 'image_text' },
    { label: 'Image', value: 'image' },
    { label: 'Links', value: 'links' },
    { label: 'Call To Action', value: 'cta' },
]

function newTile() {
    selected.value = {
        id: null, page: 'welcome', type: 'text', slug: '',
        title: '', config: {html: '<p>New tile</p>'},
        col_start: 1, row_start: 1, col_span: 1, row_span: 1, sort: tiles.value.length * 10, enabled: true,
    };
    showEdit.value = true;
}

function editTile(tile:any) {
    selected.value = JSON.parse(JSON.stringify(tile));
    showEdit.value = true;
}

function saveTile() {
    const tile = selected.value;
    const method = tile.id ? 'put' : 'post';
    const url = tile.id ? route('admin.content.update', tile.id) : route('admin.content.store')
    router[method](url, tile, {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Saved',
            });
            router.reload()
        },
    });
}

function deleteTile(tile:any) {
    router.delete(route('admin.content.destroy', tile.id), {
        onSuccess: () => router.reload()
    })
}

function saveLayout() {
    const payload = tiles.value.map((tile:any, index:number) => ({
        id: tile.id,
        sort: index*10,
        col_start: tile.col_start,
        row_start: tile.row_start,
        col_span: tile.col_span,
        row_span: tile.row_span
    }))

    router.post(route('admin.content.reorder'),
        {tiles: payload},
        {
            onSuccess: () => toast.add({ severity: 'success', summary: 'Layout updated' })
        }
    );

    function onUpload(event:any) {}
}
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl leading-tight">
                    Configure Home Page
                </h2>
                <div class="space-x-2">
                    <Button label="New Tile" @click="newTile"/>
                    <Button label="Save Layout" severity="secondary" @click="saveLayout"/>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="t in tiles" :key="t.id" class="rounded-xl border p-3 bg-white dark:bg-zinc-900">
                <div class="flex items-center justify-between mb-2">
                    <div class="font-medium">{{ t.title || t.type }}</div>
                    <div class="flex gap-2">
                        <Button icon="pi pi-pencil" text @click="editTile(t)"/>
                        <Button icon="pi pi-trash" text severity="danger" @click="deleteTile(t)" v-if="t.id"/>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <label class="flex items-center gap-2">Col start <InputText v-model.number="t.col_start" /></label>
                    <label class="flex items-center gap-2">Row start <InputText v-model.number="t.row_start" /></label>
                    <label class="flex items-center gap-2">Col span <InputText v-model.number="t.col_span" /></label>
                    <label class="flex items-center gap-2">Row span <InputText v-model.number="t.row_span" /></label>
                    <label class="flex items-center gap-2">Enabled <ToggleSwitch v-model="t.enabled" disabled /></label>
                </div>
            </div>
        </div>

        <Dialog v-model:visible="showEdit" modal header="Edit Tile" :style="{ width: '42rem' }">
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center gap-2">Title <InputText v-model="selected.title" /></label>
                    <label class="flex items-center gap-2">Slug <InputText v-model="selected.slug" placeholder="(auto if blank)"/></label>
                    <label class="flex items-center gap-2 col-span-2">
                        Type
                        <Select v-model="selected.type" :options="types" optionLabel="label" optionValue="value" class="w-60"/>
                    </label>
                </div>

                <!-- Dynamic editor by type (kept simple, extend as needed) -->
                <template v-if="selected.type === 'text'">
                    <label class="block">HTML</label>
                    <Textarea v-model="selected.config.html" rows="6" />
                </template>

                <template v-else-if="selected.type === 'newsletter'">
                    <div class="grid grid-cols-2 gap-3">
                        <label>Issue Title <InputText v-model="selected.config.issue_title" /></label>
                        <label>Issue Date <InputText v-model="selected.config.issue_date" placeholder="YYYY-MM-DD" /></label>
                        <label class="col-span-2">Summary HTML <Textarea v-model="selected.config.summary_html" rows="4" /></label>
                        <label>Link URL <InputText v-model="selected.config.link_url" /></label>
                        <label>Link Label <InputText v-model="selected.config.link_label" placeholder="Read issue" /></label>
                        <div class="col-span-2">
                            <div class="mb-2">Cover Image</div>
                            <FileUpload name="file" :url="route('admin.home.upload')"
                                        chooseLabel="Choose" uploadLabel="Upload" cancelLabel="Cancel"
                                        @uploader="() => {}" :auto="false"
                                        @before-upload="() => {}"
                                        @upload="(e)=>{ selected.config.cover_image_url = e.xhr ? JSON.parse(e.xhr.response).url : null }"/>
                            <img v-if="selected.config.cover_image_url" :src="selected.config.cover_image_url" class="h-24 mt-2 rounded"/>
                        </div>
                    </div>
                </template>

                <template v-else-if="selected.type === 'image_text'">
                    <label>Image URL <InputText v-model="selected.config.image_url" /></label>
                    <label>Alt <InputText v-model="selected.config.alt" /></label>
                    <label>Text HTML</label>
                    <Textarea v-model="selected.config.text_html" rows="4" />
                    <div class="grid grid-cols-2 gap-3">
                        <label>Link URL <InputText v-model="selected.config.link_url" /></label>
                        <label>Link Label <InputText v-model="selected.config.link_label" /></label>
                    </div>
                </template>

                <template v-else-if="selected.type === 'image'">
                    <label>Image URL <InputText v-model="selected.config.image_url" /></label>
                    <label>Alt <InputText v-model="selected.config.alt" /></label>
                    <label>Caption <InputText v-model="selected.config.caption" /></label>
                    <label>Link (optional) <InputText v-model="selected.config.link_url" /></label>
                </template>

                <template v-else-if="selected.type === 'links'">
                    <div class="text-sm opacity-70">Add simple link items in the backend payload (array of {label,url}).</div>
                </template>

                <template v-else-if="selected.type === 'cta'">
                    <label>Button Label <InputText v-model="selected.config.label" placeholder="Join us" /></label>
                    <label>URL <InputText v-model="selected.config.url" /></label>
                    <label>Description</label>
                    <Textarea v-model="selected.config.description" rows="3" />
                </template>

                <div class="flex justify-end gap-2 pt-2">
                    <Button label="Cancel" severity="secondary" @click="showEdit=false"/>
                    <Button label="Save" @click="saveTile"/>
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>

<style scoped>

</style>
