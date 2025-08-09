<script setup lang="ts">
import {useForm} from "@inertiajs/vue3";
import {InputText, Button} from "primevue";
import Editor from "primevue/editor";

const form = useForm({
    title: '',
    body: '',
    issue: ''
})

function submit() {
    form.post('/compass-points')
}

</script>

<template>
    <div>
        <h1>Create Compass Points</h1>
        <form @submit.prevent="submit">
            <InputText v-model="form.title" />
            <Editor v-model="form.body" />
            <Button label="Publish" type="submit" />
        </form>
    </div>
</template>

<style scoped>

</style>
