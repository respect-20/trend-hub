<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    posts: Object,
    platforms: Array,
    popularTags: Array,
    bookmarkedPostIds: Array,
    dismissedPostIds: Array,
    filters: Object,
    rssUrl: String,
});

const page = usePage();

const search = ref(props.filters.search ?? '');
const items = ref([...props.posts.data]);
const nextPageUrl = ref(props.posts.next_page_url);
const bookmarked = ref(new Set(props.bookmarkedPostIds));
const dismissed = ref(new Set(props.dismissedPostIds));
const receiveDigest = computed(() => page.props.auth.user.receive_digest);
const selectedIndex = ref(-1);
const rssCopied = ref(false);
const openShareMenuId = ref(null);
const linkCopiedId = ref(null);

function visit(overrides) {
    router.get(
        route('dashboard'),
        {
            sort: props.filters.sort,
            search: search.value,
            tag: props.filters.tag,
            range: props.filters.range,
            show_hidden: props.filters.show_hidden ? 1 : undefined,
            ...overrides,
        },
        { preserveScroll: true }
    );
}

function togglePlatform(platform) {
    router.patch(
        route('platform-preferences.update'),
        { source: platform.key, enabled: !platform.enabled },
        { preserveScroll: true, preserveState: true }
    );
}

function applySort(sort) {
    visit({ sort });
}

function applyRange(event) {
    visit({ range: event.target.value });
}

function applyTag(tagName) {
    visit({ tag: props.filters.tag === tagName ? undefined : tagName });
}

function toggleShowHidden() {
    visit({ show_hidden: props.filters.show_hidden ? undefined : 1 });
}

function submitSearch() {
    visit({});
}

function loadMore() {
    if (!nextPageUrl.value) return;

    router.get(
        nextPageUrl.value,
        {},
        {
            preserveState: true,
            preserveScroll: true,
            only: ['posts'],
            onSuccess: (newPage) => {
                items.value.push(...newPage.props.posts.data);
                nextPageUrl.value = newPage.props.posts.next_page_url;
            },
        }
    );
}

function isBookmarked(postId) {
    return bookmarked.value.has(postId);
}

function toggleBookmark(post) {
    if (isBookmarked(post.id)) {
        bookmarked.value.delete(post.id);
        router.delete(route('bookmarks.destroy', post.id), { preserveScroll: true, preserveState: true });
    } else {
        bookmarked.value.add(post.id);
        router.post(route('bookmarks.store', post.id), {}, { preserveScroll: true, preserveState: true });
    }
}

function isDismissed(postId) {
    return dismissed.value.has(postId);
}

function hidePost(post) {
    items.value = items.value.filter((p) => p.id !== post.id);
    router.post(route('dismissals.store', post.id), {}, { preserveScroll: true, preserveState: true });
}

function unhidePost(post) {
    dismissed.value.delete(post.id);
    router.delete(route('dismissals.destroy', post.id), { preserveScroll: true, preserveState: true });
}

function toggleDigest() {
    router.patch(
        route('digest-preference.update'),
        { receive_digest: !receiveDigest.value },
        { preserveScroll: true, preserveState: true }
    );
}

function sourceLabel(source) {
    return props.platforms.find((p) => p.key === source)?.label ?? source;
}

const sourceDomains = {
    devto: 'dev.to',
    hackernews: 'news.ycombinator.com',
    stackoverflow: 'stackoverflow.com',
    producthunt: 'producthunt.com',
    lobsters: 'lobste.rs',
    mastodon: 'mastodon.social',
};

const hackerNewsIcon =
    'data:image/svg+xml;utf8,' +
    encodeURIComponent(
        '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"><rect width="32" height="32" fill="#ff6600"/><text x="16" y="22" font-family="Verdana, sans-serif" font-size="15" font-weight="bold" fill="white" text-anchor="middle">HN</text></svg>'
    );

function sourceIconUrl(source) {
    if (source === 'hackernews') return hackerNewsIcon;

    const domain = sourceDomains[source];
    return domain ? `https://www.google.com/s2/favicons?sz=32&domain=${domain}` : null;
}

function siblingSources(post) {
    if (!post.story_group || !post.story_group.posts) return [];

    return [...new Set(post.story_group.posts.filter((p) => p.id !== post.id).map((p) => p.source))];
}

function copyRssLink() {
    navigator.clipboard.writeText(props.rssUrl).then(() => {
        rssCopied.value = true;
        setTimeout(() => (rssCopied.value = false), 1500);
    });
}

function shareLinks(post) {
    const url = encodeURIComponent(post.url);
    const title = encodeURIComponent(post.title);

    return [
        { name: 'X / Twitter', href: `https://twitter.com/intent/tweet?text=${title}&url=${url}` },
        { name: 'Facebook', href: `https://www.facebook.com/sharer/sharer.php?u=${url}` },
        { name: 'LinkedIn', href: `https://www.linkedin.com/sharing/share-offsite/?url=${url}` },
        { name: 'Reddit', href: `https://reddit.com/submit?url=${url}&title=${title}` },
        { name: 'WhatsApp', href: `https://wa.me/?text=${title}%20${url}` },
        { name: 'Email', href: `mailto:?subject=${title}&body=${url}` },
    ];
}

function toggleShareMenu(post) {
    openShareMenuId.value = openShareMenuId.value === post.id ? null : post.id;
}

function openShareLink(href) {
    window.open(href, '_blank', 'noopener,noreferrer,width=600,height=500');
    openShareMenuId.value = null;
}

async function nativeShare(post) {
    if (navigator.share) {
        try {
            await navigator.share({ title: post.title, url: post.url });
        } catch (e) {
            // user cancelled the native share sheet, nothing to do
        }
        return;
    }

    toggleShareMenu(post);
}

function copyPostLink(post) {
    navigator.clipboard.writeText(post.url).then(() => {
        linkCopiedId.value = post.id;
        setTimeout(() => (linkCopiedId.value = null), 1500);
    });
}

function closeShareMenus() {
    openShareMenuId.value = null;
}

function isTypingInField() {
    const tag = document.activeElement?.tagName;
    return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT';
}

function handleKeydown(event) {
    if (isTypingInField() || event.metaKey || event.ctrlKey || event.altKey) return;

    if (event.key === 'j') {
        selectedIndex.value = Math.min(selectedIndex.value + 1, items.value.length - 1);
        scrollToSelected();
    } else if (event.key === 'k') {
        selectedIndex.value = Math.max(selectedIndex.value - 1, 0);
        scrollToSelected();
    } else if (event.key === 'o' || event.key === 'Enter') {
        const post = items.value[selectedIndex.value];
        if (post) window.open(post.url, '_blank', 'noopener,noreferrer');
    } else if (event.key === 'b') {
        const post = items.value[selectedIndex.value];
        if (post) toggleBookmark(post);
    } else if (event.key === 'x') {
        const post = items.value[selectedIndex.value];
        if (post) hidePost(post);
    }
}

function scrollToSelected() {
    requestAnimationFrame(() => {
        document.querySelector(`[data-post-index="${selectedIndex.value}"]`)?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    });
}

function handleOutsideClick(event) {
    if (!event.target.closest('[data-share-container]')) {
        closeShareMenus();
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
    window.addEventListener('click', handleOutsideClick);
});
onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    window.removeEventListener('click', handleOutsideClick);
});
</script>

<template>
    <Head title="Trending" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Trending</h2>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Sidebar -->
                <aside class="order-2 md:order-1 md:col-span-1 space-y-6">
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-5">
                        <h3 class="font-semibold text-sm text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-4">
                            Platforms
                        </h3>
                        <ul class="space-y-4">
                            <li v-for="platform in platforms" :key="platform.key">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="flex items-center gap-2 min-w-0 text-sm text-gray-800 dark:text-gray-200">
                                        <img :src="sourceIconUrl(platform.key)" class="w-4 h-4 rounded-sm flex-shrink-0" alt="" />
                                        <span class="truncate">{{ platform.label }}</span>
                                    </span>
                                    <button
                                        type="button"
                                        @click="togglePlatform(platform)"
                                        :class="[
                                            'relative inline-flex h-5 w-9 flex-shrink-0 items-center rounded-full transition-colors',
                                            platform.enabled ? 'bg-indigo-600' : 'bg-gray-300 dark:bg-gray-600',
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform',
                                                platform.enabled ? 'translate-x-4.5 ml-1' : 'translate-x-1',
                                            ]"
                                        />
                                    </button>
                                </div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 mt-1 leading-relaxed">
                                    <span v-if="platform.status === 'success'">✓ {{ platform.lastRunAt }} ({{ platform.postsFetched }})</span>
                                    <span v-else-if="platform.status === 'failed'" class="text-red-500">✗ failed {{ platform.lastRunAt }}</span>
                                    <span v-else-if="platform.status === 'not_configured'" class="text-gray-400 dark:text-gray-500">⚙ needs setup</span>
                                    <span v-else>not fetched yet</span>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-5" v-if="popularTags.length">
                        <h3 class="font-semibold text-sm text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-4">
                            Tags
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="tag in popularTags"
                                :key="tag.id"
                                type="button"
                                @click="applyTag(tag.name)"
                                :class="[
                                    'px-2.5 py-1 text-xs rounded-full border',
                                    filters.tag === tag.name
                                        ? 'bg-indigo-600 text-white border-indigo-600'
                                        : 'bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600',
                                ]"
                            >
                                {{ tag.name }} ({{ tag.posts_count }})
                            </button>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-5 space-y-3">
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" :checked="receiveDigest" @change="toggleDigest" class="rounded border-gray-300 dark:border-gray-600" />
                            Email me a daily digest
                        </label>
                        <button
                            type="button"
                            @click="toggleShowHidden"
                            class="block text-xs text-indigo-600 dark:text-indigo-400 hover:underline"
                        >
                            {{ filters.show_hidden ? 'Hide dismissed posts' : 'Show dismissed posts' }}
                        </button>
                        <button
                            type="button"
                            @click="copyRssLink"
                            class="block text-xs text-indigo-600 dark:text-indigo-400 hover:underline"
                        >
                            {{ rssCopied ? 'Copied!' : 'Copy RSS feed link' }}
                        </button>
                    </div>

                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-5 text-xs text-gray-500 dark:text-gray-400 space-y-1.5">
                        <p class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Keyboard shortcuts</p>
                        <p><kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">j</kbd>/<kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">k</kbd> navigate</p>
                        <p><kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">o</kbd> open, <kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">b</kbd> bookmark, <kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">x</kbd> hide</p>
                    </div>
                </aside>

                <!-- Feed -->
                <section class="order-1 md:order-2 md:col-span-3">
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 mb-4 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
                        <form @submit.prevent="submitSearch" class="flex-1 flex gap-2">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search trending stories..."
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm"
                            />
                            <button
                                type="submit"
                                class="px-3 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700"
                            >
                                Search
                            </button>
                        </form>

                        <select
                            :value="filters.range"
                            @change="applyRange"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm"
                        >
                            <option value="all">All time</option>
                            <option value="today">Today</option>
                            <option value="week">This week</option>
                            <option value="month">This month</option>
                        </select>

                        <div class="flex gap-2">
                            <button
                                type="button"
                                @click="applySort('trending')"
                                :class="[
                                    'px-3 py-2 text-sm rounded-md',
                                    filters.sort !== 'recent' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
                                ]"
                            >
                                Trending
                            </button>
                            <button
                                type="button"
                                @click="applySort('recent')"
                                :class="[
                                    'px-3 py-2 text-sm rounded-md',
                                    filters.sort === 'recent' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
                                ]"
                            >
                                Recent
                            </button>
                        </div>
                    </div>

                    <div v-if="filters.tag" class="mb-3 text-sm text-gray-600 dark:text-gray-400">
                        Filtering by tag: <strong>{{ filters.tag }}</strong>
                        <button type="button" @click="applyTag(filters.tag)" class="ml-2 text-indigo-600 dark:text-indigo-400 hover:underline">clear</button>
                    </div>

                    <div v-if="items.length === 0" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-8 text-center text-gray-500 dark:text-gray-400">
                        No posts yet. Run <code>php artisan app:fetch-trending</code> to pull in stories.
                    </div>

                    <ul v-else class="space-y-3">
                        <li
                            v-for="(post, index) in items"
                            :key="post.id"
                            :data-post-index="index"
                            @click="selectedIndex = index"
                            :class="[
                                'bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 cursor-pointer',
                                selectedIndex === index ? 'ring-2 ring-indigo-500' : '',
                            ]"
                        >
                            <div class="flex items-start gap-4">
                                <img
                                    v-if="post.thumbnail_url"
                                    :src="post.thumbnail_url"
                                    class="w-20 h-20 object-cover rounded-md flex-shrink-0 hidden sm:block"
                                    alt=""
                                />
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300">
                                            <img :src="sourceIconUrl(post.source)" class="w-3.5 h-3.5 rounded-sm" alt="" />
                                            {{ sourceLabel(post.source) }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">score: {{ post.trending_score }}</span>
                                        <span v-if="post.velocity >= 15" class="text-xs font-medium text-orange-600 dark:text-orange-400">🔥 rising</span>
                                        <span v-if="siblingSources(post).length" class="text-xs text-gray-500 dark:text-gray-400">
                                            also on {{ siblingSources(post).map(sourceLabel).join(', ') }}
                                        </span>
                                    </div>
                                    <a :href="post.url" target="_blank" rel="noopener noreferrer" class="font-medium text-gray-900 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400">
                                        {{ post.title }}
                                    </a>
                                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        <span v-if="post.author">by {{ post.author }}</span>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end gap-2 flex-shrink-0">
                                    <button
                                        type="button"
                                        @click="toggleBookmark(post)"
                                        class="text-sm"
                                        :class="isBookmarked(post.id) ? 'text-yellow-500' : 'text-gray-300 dark:text-gray-600 hover:text-yellow-500'"
                                    >
                                        ★
                                    </button>
                                    <div class="relative" data-share-container>
                                        <button
                                            type="button"
                                            @click="nativeShare(post)"
                                            class="text-sm text-gray-300 dark:text-gray-600 hover:text-indigo-500"
                                            title="Share"
                                        >
                                            ⤴
                                        </button>
                                        <div
                                            v-if="openShareMenuId === post.id"
                                            class="absolute right-0 z-10 mt-1 w-40 bg-white dark:bg-gray-800 shadow-lg rounded-md border border-gray-100 dark:border-gray-700 py-1"
                                        >
                                            <button
                                                v-for="link in shareLinks(post)"
                                                :key="link.name"
                                                type="button"
                                                @click="openShareLink(link.href)"
                                                class="block w-full text-left px-3 py-1.5 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                                            >
                                                {{ link.name }}
                                            </button>
                                            <button
                                                type="button"
                                                @click="copyPostLink(post)"
                                                class="block w-full text-left px-3 py-1.5 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                                            >
                                                {{ linkCopiedId === post.id ? 'Copied!' : 'Copy link' }}
                                            </button>
                                        </div>
                                    </div>
                                    <button
                                        v-if="isDismissed(post.id)"
                                        type="button"
                                        @click="unhidePost(post)"
                                        class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline"
                                    >
                                        unhide
                                    </button>
                                    <button
                                        v-else
                                        type="button"
                                        @click="hidePost(post)"
                                        class="text-xs text-gray-300 dark:text-gray-600 hover:text-gray-500"
                                    >
                                        ✕
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>

                    <div class="mt-4 text-center" v-if="nextPageUrl">
                        <button
                            type="button"
                            @click="loadMore"
                            class="px-4 py-2 bg-white dark:bg-gray-800 text-sm text-gray-700 dark:text-gray-300 rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            Load more
                        </button>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
