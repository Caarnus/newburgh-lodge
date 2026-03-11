<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import { ConfirmDialog, ConfirmPopup, DynamicDialog, Menubar, Toast, Badge } from 'primevue';
import { useDark, useToggle } from '@vueuse/core';
import { route } from 'ziggy-js';

const isDark = useDark({ valueDark: 'app-dark' });
const toggleDark = useToggle(isDark);
const $page = usePage();
const $user = $page.props.auth?.user;
const canManagePeople = $page.props.can.manage.people;

const isAuthenticated = !!$user;

defineProps({
    title: String,
});

const page = usePage();

const navMenuItems = ref([
    { label: 'Home', url: route('dashboard') },
    {
        label: 'News',
        items: [
            { label: $page.props.site.newsletterLabel, url: route('newsletters.index'), visible: isAuthenticated },
            { label: 'Upcoming Events', url: route('events.index') },
            { label: 'Photo Gallery', url: route('gallery.index') },
        ],
    },
    {
        label: 'About',
        items: [
            { label: 'History', url: route('history') },
            { label: 'Officers', url: route('officers') },
            { label: 'Past Masters', url: route('past-masters.index') },
            { label: 'Masonic FAQ', url: route('faq') },
            {
                label: 'Directions',
                url: 'https://www.google.com/maps/dir/?api=1&destination=720+Filmore+St+Newburgh+IN+47630&travelmode=driving',
                target: '_blank',
            },
        ],
    },
    { label: 'Scholarship', url: route('scholarship.intro') },
    { label: 'Contact Us', url: route('contact') },
    {
        label: 'Links',
        items: [
            { label: 'Indiana Grand Lodge', url: 'https://www.indianafreemasons.com/', target: '_blank' },
            { label: 'Indiana Grand Lodge Magazine', url: 'https://www.indianafreemasonmagazine.com/', target: '_blank' },
            { label: 'Historic Newburgh, IN', url: 'https://www.historicnewburgh.org/', target: '_blank' },
            { label: 'Evansville Lodge #64 F&AM', url: 'https://www.evansvillelodge.org/', target: '_blank' },
            { label: 'Reed Lodge #316 F&AM', url: 'https://www.reedlodge316.org/', target: '_blank' },
            { label: 'Hadi Shrine', url: 'https://www.hadishrine.org/', target: '_blank' },
            { label: 'Newburgh Lodge Facebook', url: 'https://www.facebook.com/newburghlodge174', target: '_blank' },
        ],
    },
    {
        label: 'Admin',
        visible: $page.props.can.admin.users,
        items: [
            { label: 'Users', url: route('admin.users.index') },
        ],
    },
    {
        label: 'Manage',
        visible: $page.props.can.manage.content || $page.props.can.manage.scholarships || canManagePeople.any,
        items: [
            { label: 'Jeopardy', url: route('jeopardy.index'), visible: $page.props.can.manage.content },
            { label: 'Manage Content', url: route('admin.content.index'), visible: $page.props.can.manage.content },
            { label: 'Manage Gallery', url: route('admin.gallery.index'), visible: $page.props.can.manage.gallery },
            { label: 'Scholarship Applications', url: route('manage.scholarships.index'), visible: $page.props.can.manage.scholarships },
            { label: 'Member Directory', url: route('manage.member-directory.index'), visible: canManagePeople.directory },
        ],
    },
    {
        label: page.props.auth?.user?.name ?? 'Sign In',
        class: 'ml-auto',
        items: [
            { label: 'Log In', url: route('login'), visible: !page.props.auth.user },
            { label: 'Register', url: route('register'), visible: !page.props.auth.user },
            { label: 'Profile', url: route('profile.show'), active: route().current('profile.show'), visible: !!page.props.auth.user },
            { label: 'Log Out', command: () => logout(), visible: !!page.props.auth.user },
        ],
    },
    { icon: isDark ? 'pi pi-sun' : 'pi pi-moon', class: 'ml-10 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800', command: () => toggleDark() },
]);

const logout = () => {
    router.post(route('logout'), {}, {
        onFinish: () => {
            router.get('/');
        },
    });
};
</script>

<template>
    <div>
        <Head :title="title" />

        <Toast />
        <ConfirmDialog />
        <ConfirmPopup />
        <DynamicDialog />

        <Banner />

        <div class="min-h-screen bg-surface-100 dark:bg-surface-800">
            <nav class="bg-surface-0 dark:bg-surface-800">
                <div class="flex">
                    <Menubar :model="navMenuItems" class="w-full rounded-b-none gap-2">
                        <template #start>
                            <a :href="route('dashboard')">
                                <img alt="Home" src="/img/logo.svg" width="50px" />
                            </a>
                        </template>
                        <template #item="{ item, props, hasSubmenu, root }">
                            <a v-if="item.label || item.separator || item.icon" v-ripple class="flex items-center" v-bind="props.action" :href="item.url" :target="item.target ?? null">
                                <img v-if="item.img" :alt="item.alt" :src="item.img" class="rounded-full mr-2" width="40px" />
                                <span v-if="item.icon" :class="item.icon" />
                                <span v-if="item.label" :class="{ 'ml-2': item.icon }">{{ item.label }}</span>
                                <Badge v-if="item.badge" :class="{ 'ml-auto': !root, 'ml-2': root }" :value="item.badge" />
                                <span v-if="item.shortcut" class="ml-auto border border-surface-200 dark:border-surface-500 rounded-md bg-surface-100 dark:bg-surface-800 text-xs p-1">{{ item.shortcut }}</span>
                                <i v-if="hasSubmenu" :class="['pi pi-angle-down text-primary-500 dark:text-primary-400-500 dark:text-primary-400', { 'pi-angle-down ml-2': root, 'pi-angle-right ml-auto': !root }]" />
                            </a>
                        </template>
                    </Menubar>
                </div>
            </nav>

            <header v-if="$slots.header" class="bg-surface-50 dark:bg-surface-900 shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 text-surface-900 dark:text-surface-100">
                    <slot name="header" />
                </div>
            </header>

            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
