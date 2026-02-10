#!/usr/bin/env python3
"""Patch LikeYou.vue Recent and Matches sections with modern grid layout."""
path = 'resources/js/pages/LikeYou.vue'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

# Unicode apostrophe used in the file
APOS = '\u2019'

# Recent section old content (with correct apostrophe)
old_recent = f'''                    <div v-if="whoLikedMeLoading && whoLikedMeList.length === 0" class="flex justify-center py-20">
                        <div class="w-10 h-10 border-4 border-primary border-t-transparent rounded-full animate-spin" />
                    </div>
                    <div v-else-if="whoLikedMeList.length === 0" class="flex flex-col items-center justify-center py-16 px-6 text-gray-500">
                        <Heart class="w-14 h-14 mb-3 opacity-50" />
                        <p class="text-center font-medium">No one to match back yet</p>
                        <p class="text-sm mt-1 text-center">When someone likes you, they{APOS}ll show up here.</p>
                        <button type="button" @click="setTab('discover')" class="mt-4 px-4 py-2 rounded-xl bg-primary text-primary-foreground font-medium text-sm hover:opacity-90">Discover</button>
                    </div>
                    <ul v-else class="divide-y divide-gray-100">
                        <li
                            v-for="u in whoLikedMeList"
                            :key="u.id"
                            class="flex gap-4 px-4 py-3"
                        >
                            <button type="button" @click="openProfileForMatch(u.id)" class="w-16 h-16 rounded-full overflow-hidden bg-gray-100 flex-shrink-0 ring-2 ring-white shadow">
                                <img v-if="u.profile_picture" :src="profilePictureSrc(u.profile_picture)" :alt="u.display_name" class="w-full h-full object-cover" />
                                <div v-else class="w-full h-full flex items-center justify-center text-blue-600 font-bold text-xl">{{ (u.display_name || u.fullname || '?').charAt(0).toUpperCase() }}</div>
                            </button>
                            <div class="flex-1 min-w-0">
                                <button type="button" @click="openProfileForMatch(u.id)" class="text-left block w-full">
                                    <p class="font-semibold text-gray-900 truncate">{{ u.fullname || u.display_name }}</p>
                                    <p v-if="u.campus" class="text-xs text-gray-500 truncate">{{ u.campus }}</p>
                                </button>
                                <p class="text-xs text-gray-600 mt-1 flex items-center gap-1">
                                    <component :is="intentIcon(u.their_intent)" class="w-3.5 h-3.5" />
                                    Wants to match ({{ intentLabel(u.their_intent) }})
                                </p>
                                <div class="flex gap-2 mt-3">
                                    <button type="button" :disabled="matchBackActionUserId === u.id" @click="pass(u)" class="px-4 py-2 rounded-xl bg-gray-200 text-gray-700 text-sm font-semibold hover:bg-gray-300 disabled:opacity-50">Pass</button>
                                    <button type="button" :disabled="matchBackActionUserId === u.id" @click="matchBack(u)" class="px-4 py-2 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 text-white text-sm font-semibold hover:opacity-95 disabled:opacity-50">{{ matchBackActionUserId === u.id ? '…' : 'Match back' }}</button>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <div v-if="whoLikedMeList.length > 0 && whoLikedMePage < whoLikedMeLastPage" class="p-4 flex justify-center">
                        <button type="button" :disabled="whoLikedMeLoadingMore" class="px-4 py-2 rounded-xl bg-gray-100 text-gray-700 text-sm font-medium disabled:opacity-50" @click="fetchWhoLikedMe(whoLikedMePage + 1)">{{ whoLikedMeLoadingMore ? 'Loading…' : 'Load more' }}</button>
                    </div>'''

new_recent = '''                    <div v-if="whoLikedMeLoading && whoLikedMeList.length === 0" class="flex justify-center py-20">
                        <div class="flex flex-col items-center gap-3">
                            <div class="w-12 h-12 border-4 border-pink-500 border-t-transparent rounded-full animate-spin" />
                            <p class="text-sm text-gray-600 font-medium">Loading your admirers...</p>
                        </div>
                    </div>
                    <div v-else-if="whoLikedMeList.length === 0" class="flex flex-col items-center justify-center py-20 px-6">
                        <div class="relative mb-6">
                            <div class="w-24 h-24 bg-gradient-to-br from-pink-400 to-rose-500 rounded-full flex items-center justify-center shadow-lg">
                                <Heart class="w-12 h-12 text-white fill-white/90 animate-pulse" />
                            </div>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">No Likes Yet</h3>
                        <p class="text-center text-gray-600 mb-6 max-w-sm">
                            Start swiping to find your match! When someone likes you, they'll appear here.
                        </p>
                        <button
                            type="button"
                            @click="setTab('discover')"
                            class="px-8 py-3 rounded-full bg-gradient-to-r from-pink-500 via-red-500 to-rose-600 text-white font-semibold shadow-xl hover:shadow-2xl hover:scale-105 transition-all duration-300"
                        >
                            Start Discovering
                        </button>
                    </div>
                    <div v-else class="px-4 py-6">
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <div
                                v-for="(u, index) in whoLikedMeList"
                                :key="u.id"
                                class="matchback-card group relative bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2"
                                :style="{ '--index': index }"
                            >
                                <button type="button" @click="openProfileForMatch(u.id)" class="block w-full text-left">
                                    <div class="relative aspect-[3/4] overflow-hidden">
                                        <img
                                            v-if="u.profile_picture"
                                            :src="profilePictureSrc(u.profile_picture)"
                                            :alt="u.display_name"
                                            class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                                        />
                                        <div
                                            v-else
                                            class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-400 to-cyan-500 text-white font-bold text-4xl"
                                        >
                                            {{ (u.display_name || u.fullname || '?').charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent" />
                                        <div class="absolute top-3 right-3 px-2.5 py-1.5 rounded-full bg-white/90 backdrop-blur-sm flex items-center gap-1.5 shadow-lg">
                                            <component :is="intentIcon(u.their_intent)" class="w-4 h-4" :class="intentColor(u.their_intent)" />
                                        </div>
                                        <div class="absolute bottom-0 left-0 right-0 p-3">
                                            <h4 class="text-white font-bold text-lg truncate drop-shadow-lg">
                                                {{ u.display_name || u.fullname }}
                                            </h4>
                                            <p v-if="u.campus" class="text-white/90 text-xs truncate drop-shadow">{{ u.campus }}</p>
                                        </div>
                                    </div>
                                </button>
                                <div class="p-3 flex gap-2">
                                    <button
                                        type="button"
                                        :disabled="matchBackActionUserId === u.id"
                                        @click="pass(u)"
                                        class="flex-1 py-2.5 rounded-xl bg-gray-100 text-gray-700 text-sm font-semibold hover:bg-gray-200 transition-colors disabled:opacity-50 flex items-center justify-center gap-1"
                                    >
                                        <X class="w-4 h-4" />
                                        Pass
                                    </button>
                                    <button
                                        type="button"
                                        :disabled="matchBackActionUserId === u.id"
                                        @click="matchBack(u)"
                                        class="flex-1 py-2.5 rounded-xl bg-gradient-to-r from-pink-500 to-rose-600 text-white text-sm font-semibold hover:shadow-lg transition-all disabled:opacity-50 flex items-center justify-center gap-1"
                                    >
                                        <Heart class="w-4 h-4" :class="matchBackActionUserId === u.id ? '' : 'fill-white'" />
                                        {{ matchBackActionUserId === u.id ? '…' : 'Like' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-if="whoLikedMePage < whoLikedMeLastPage" class="mt-8 flex justify-center">
                            <button
                                type="button"
                                :disabled="whoLikedMeLoadingMore"
                                class="px-8 py-3 rounded-full bg-white border-2 border-gray-200 text-gray-700 text-sm font-semibold hover:border-pink-300 hover:text-pink-600 transition-all disabled:opacity-50 shadow-md hover:shadow-lg"
                                @click="fetchWhoLikedMe(whoLikedMePage + 1)"
                            >
                                {{ whoLikedMeLoadingMore ? 'Loading…' : 'Load More' }}
                            </button>
                        </div>
                    </div>'''

# Matches section
old_matches = f'''                <div class="flex-1 min-h-0 overflow-y-auto bg-white pb-6">
                    <div v-if="mutualLoading && mutualList.length === 0" class="flex justify-center py-16">
                        <div class="w-10 h-10 border-4 border-primary border-t-transparent rounded-full animate-spin" />
                    </div>
                    <div v-else-if="mutualList.length === 0" class="flex flex-col items-center justify-center py-16 px-6 text-gray-500">
                        <Heart class="w-14 h-14 mb-3 opacity-50" />
                        <p class="text-center font-medium">No matches yet</p>
                        <p class="text-sm mt-1 text-center">When you and someone like each other, they{APOS}ll appear here.</p>
                        <button type="button" @click="setTab('discover')" class="mt-4 px-4 py-2 rounded-xl bg-primary text-primary-foreground font-medium text-sm hover:opacity-90">Discover</button>
                    </div>
                    <ul v-else class="divide-y divide-gray-100">
                        <li v-for="u in mutualList" :key="u.id" class="flex items-center gap-4 px-4 py-3">
                            <button type="button" @click="openProfileForMatch(u.id)" class="w-16 h-16 rounded-full overflow-hidden bg-gray-100 flex-shrink-0 ring-2 ring-white shadow">
                                <img v-if="u.profile_picture" :src="profilePictureSrc(u.profile_picture)" :alt="u.display_name" class="w-full h-full object-cover" />
                                <div v-else class="w-full h-full flex items-center justify-center text-blue-600 font-bold text-xl">{{ (u.display_name || u.fullname || '?').charAt(0).toUpperCase() }}</div>
                            </button>
                            <div class="min-w-0 flex-1">
                                <button type="button" @click="openProfileForMatch(u.id)" class="text-left block w-full">
                                    <p class="font-semibold text-gray-900 truncate">{{ u.fullname || u.display_name }}</p>
                                    <p v-if="u.campus" class="text-xs text-gray-500 truncate">{{ u.campus }}</p>
                                    <p v-if="u.matched_at" class="text-xs text-gray-400 mt-0.5">Matched {{ formatMatchedAt(u.matched_at) }}</p>
                                </button>
                                <p v-if="u.intent" class="text-xs text-gray-500 flex items-center gap-1 mt-0.5">
                                    <component :is="intentIcon(u.intent)" class="w-3 h-3" />
                                    {{ intentLabel(u.intent) }}
                                </p>
                            </div>
                            <button type="button" @click="openChatForMatch(u.id)" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 text-white text-sm font-semibold hover:opacity-95 shrink-0">
                                <MessageCircle class="w-4 h-4" />
                                Message
                            </button>
                        </li>
                    </ul>
                    <div v-if="mutualList.length > 0 && mutualPage < mutualLastPage" class="p-4 flex justify-center">
                        <button type="button" :disabled="mutualLoadingMore" class="px-4 py-2 rounded-xl bg-gray-100 text-gray-700 text-sm font-medium disabled:opacity-50" @click="fetchMutual(mutualPage + 1)">{{ mutualLoadingMore ? 'Loading…' : 'Load more' }}</button>
                    </div>
                </div>'''

new_matches = '''                <div class="flex-1 min-h-0 overflow-y-auto bg-gradient-to-br from-cyan-50 via-white to-blue-50 pb-6">
                    <div v-if="mutualLoading && mutualList.length === 0" class="flex justify-center py-20">
                        <div class="flex flex-col items-center gap-3">
                            <div class="w-12 h-12 border-4 border-cyan-500 border-t-transparent rounded-full animate-spin" />
                            <p class="text-sm text-gray-600 font-medium">Loading your matches...</p>
                        </div>
                    </div>
                    <div v-else-if="mutualList.length === 0" class="flex flex-col items-center justify-center py-20 px-6">
                        <div class="relative mb-6">
                            <div class="w-24 h-24 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center shadow-lg">
                                <Heart class="w-12 h-12 text-white fill-white/90 animate-pulse" />
                            </div>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">No Matches Yet</h3>
                        <p class="text-center text-gray-600 mb-6 max-w-sm">
                            When you and someone like each other, you'll both appear here ready to chat!
                        </p>
                        <button
                            type="button"
                            @click="setTab('discover')"
                            class="px-8 py-3 rounded-full bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-semibold shadow-xl hover:shadow-2xl hover:scale-105 transition-all duration-300"
                        >
                            Start Swiping
                        </button>
                    </div>
                    <div v-else class="px-4 py-6">
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <div
                                v-for="(u, index) in mutualList"
                                :key="u.id"
                                class="matches-card group relative bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2"
                                :style="{ '--index': index }"
                            >
                                <button type="button" @click="openProfileForMatch(u.id)" class="block w-full text-left">
                                    <div class="relative aspect-[3/4] overflow-hidden">
                                        <img
                                            v-if="u.profile_picture"
                                            :src="profilePictureSrc(u.profile_picture)"
                                            :alt="u.display_name"
                                            class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                                        />
                                        <div
                                            v-else
                                            class="w-full h-full flex items-center justify-center bg-gradient-to-br from-cyan-400 to-blue-500 text-white font-bold text-4xl"
                                        >
                                            {{ (u.display_name || u.fullname || '?').charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent" />
                                        <div class="absolute top-3 right-3 px-2.5 py-1.5 rounded-full bg-gradient-to-r from-pink-500 to-rose-500 text-white flex items-center gap-1.5 shadow-lg text-xs font-bold">
                                            <Heart class="w-3.5 h-3.5 fill-white" />
                                            Match
                                        </div>
                                        <div class="absolute bottom-0 left-0 right-0 p-3">
                                            <h4 class="text-white font-bold text-lg truncate drop-shadow-lg">
                                                {{ u.display_name || u.fullname }}
                                            </h4>
                                            <p v-if="u.campus" class="text-white/90 text-xs truncate drop-shadow">{{ u.campus }}</p>
                                            <p v-if="u.matched_at" class="text-white/80 text-xs mt-1 drop-shadow">
                                                Matched {{ formatMatchedAt(u.matched_at) }}
                                            </p>
                                        </div>
                                    </div>
                                </button>
                                <div class="p-3">
                                    <button
                                        type="button"
                                        @click="openChatForMatch(u.id)"
                                        class="w-full py-3 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 text-white text-sm font-semibold hover:shadow-xl transition-all flex items-center justify-center gap-2 group/btn"
                                    >
                                        <MessageCircle class="w-4 h-4 group-hover/btn:scale-110 transition-transform" />
                                        Send Message
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-if="mutualPage < mutualLastPage" class="mt-8 flex justify-center">
                            <button
                                type="button"
                                :disabled="mutualLoadingMore"
                                class="px-8 py-3 rounded-full bg-white border-2 border-gray-200 text-gray-700 text-sm font-semibold hover:border-cyan-300 hover:text-cyan-600 transition-all disabled:opacity-50 shadow-md hover:shadow-lg"
                                @click="fetchMutual(mutualPage + 1)"
                            >
                                {{ mutualLoadingMore ? 'Loading…' : 'Load More' }}
                            </button>
                        </div>
                    </div>
                </div>'''

if old_recent in content:
    content = content.replace(old_recent, new_recent)
    print("Replaced Recent section")
else:
    print("ERROR: Recent section not found")

if old_matches in content:
    content = content.replace(old_matches, new_matches)
    print("Replaced Matches section")
else:
    print("ERROR: Matches section not found")

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)
print("Done.")
