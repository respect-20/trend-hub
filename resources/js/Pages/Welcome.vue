<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});

const features = [
    {
        title: 'One feed, six platforms',
        description:
            'dev.to, Hacker News, Stack Overflow, Product Hunt, Lobsters, and Mastodon — pulled together and ranked by a single trending score.',
    },
    {
        title: 'You choose what shows up',
        description:
            'Toggle any platform on or off from the sidebar, filter by tag or time range, and search across titles and tags.',
    },
    {
        title: "Never lose a story you liked",
        description:
            'Bookmark posts to read later, hide the ones you\'re done with, and get a daily digest email of your top stories.',
    },
    {
        title: 'Read it your way',
        description:
            'Export your personalized feed as RSS, or fly through it with keyboard shortcuts — j/k to navigate, b to bookmark, x to hide.',
    },
];
</script>

<template>
    <Head title="TrendHub — All your trending stories, in one place" />

    <div class="relative min-h-screen bg-gray-50 dark:bg-gray-900">
        <div v-if="canLogin" class="absolute top-0 right-0 p-6 text-end">
            <Link
                v-if="$page.props.auth.user"
                :href="route('dashboard')"
                class="font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                >Dashboard</Link
            >

            <template v-else>
                <Link
                    :href="route('login')"
                    class="font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                    >Log in</Link
                >

                <Link
                    v-if="canRegister"
                    :href="route('register')"
                    class="ms-4 font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                    >Register</Link
                >
            </template>
        </div>

        <div class="max-w-5xl mx-auto px-6 pt-28 pb-16">
            <div class="flex flex-col items-center text-center">
                <ApplicationLogo class="w-12 h-12 fill-current text-indigo-600" />

                <h1 class="mt-6 text-4xl sm:text-5xl font-bold tracking-tight text-gray-900 dark:text-white">
                    All your trending stories, in one place
                </h1>

                <p class="mt-4 max-w-2xl text-lg text-gray-600 dark:text-gray-400">
                    TrendHub aggregates what's trending across dev.to, Hacker News, Stack Overflow, Product Hunt,
                    Lobsters, and Mastodon into a single ranked feed — so you don't have to check six sites a day.
                </p>

                <div class="mt-8 flex gap-3">
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700"
                        >Get started — it's free</Link
                    >
                    <Link
                        v-if="canLogin"
                        :href="route('login')"
                        class="px-5 py-2.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm font-semibold rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700"
                        >Log in</Link
                    >
                </div>
            </div>

            <div class="mt-20 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div
                    v-for="feature in features"
                    :key="feature.title"
                    class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm"
                >
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ feature.title }}</h2>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                        {{ feature.description }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
