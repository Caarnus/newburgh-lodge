<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
</script>

<template>
    <div>
        <div class="p-6 lg:p-8 bg-surface-0 border-b border-surface-200">
            <div class="flex flex-row gap-x-4">
                <ApplicationLogo class="block h-48 w-auto" />
                <div>
                    <h1 class="mt-8 text-2xl font-medium text-surface-900">
                        Newburgh Lodge #174 F&AM
                    </h1>

                    <p class="mt-6 text-surface-500 leading-relaxed">
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut
                        labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris
                        nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit
                        esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt
                        in culpa qui officia deserunt mollit anim id est laborum.
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-surface-200 bg-opacity-25 grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8 p-6 lg:p-8">
            <div>
                <div class="flex items-center">
                    <i class="pi pi-book text-surface-400"></i>
                    <h2 class="ms-3 text-xl font-semibold text-surface-900">
                        <a href="/">Compass Points</a>
                    </h2>
                </div>

                <p class="mt-4 text-surface-500 text-sm leading-relaxed">
                    Read the latest Compass Points newsletter to get all the up to date information on the events going
                    on in the lodge and stay connected.
                </p>

                <p class="mt-4 text-sm">
                    <a href="/" class="inline-flex items-center font-semibold text-indigo-700">
                        Read the newsleter

                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="ms-1 size-5 fill-indigo-500">
                            <path fill-rule="evenodd" d="M5 10a.75.75 0 01.75-.75h6.638L10.23 7.29a.75.75 0
                            111.04-1.08l3.5 3.25a.75.75 0 010 1.08l-3.5 3.25a.75.75 0 11-1.04-1.08l2.158-1.96H5.75A.75.75
                            0 015 10z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </p>
            </div>

            <div>
                <div class="flex items-center">
                    <i class="pi pi-camera text-surface-400"></i>
                    <h2 class="ms-3 text-xl font-semibold text-surface-900">
                        <a href="/">Video Message</a>
                    </h2>
                </div>

                <p class="mt-4 text-surface-500 text-sm leading-relaxed">
                    Here is a video message from the Worshipful Master about something that is important and worth
                    making a video about that we can determine later.
                </p>

                <p class="mt-4 text-sm">
                    <a href="/" class="inline-flex items-center font-semibold text-indigo-700">
                        Watching the video

                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="ms-1 size-5 fill-indigo-500">
                            <path fill-rule="evenodd" d="M5 10a.75.75 0 01.75-.75h6.638L10.23 7.29a.75.75 0
                            111.04-1.08l3.5 3.25a.75.75 0 010 1.08l-3.5 3.25a.75.75 0 11-1.04-1.08l2.158-1.96H5.75A.75.75
                            0 015 10z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </p>
            </div>

            <div>
                <div class="flex items-center">
                    <i class="pi pi-calendar text-surface-400"></i>
                    <h2 class="ms-3 text-xl font-semibold text-surface-900">
                        <a href="/">Calendar</a>
                    </h2>
                </div>

                <p class="mt-4 text-surface-500 text-sm leading-relaxed">
                    This is a link to our Google calendar of lodge events that are coming up, it could also be a page on
                    this site that displays upcoming events.
                </p>
            </div>

            <div>
                <div class="flex items-center">
                    <i class="pi pi-users text-surface-400"></i>
                    <h2 class="ms-3 text-xl font-semibold text-surface-900">
                        Officers
                    </h2>
                </div>

                <p class="mt-4 text-surface-500 text-sm leading-relaxed">
                    This could be a link to a list of the current officers for the lodge or this list of past W.M. or
                    something else entirely.
                </p>
            </div>
        </div>
    </div>
</template>
