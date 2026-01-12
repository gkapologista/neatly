<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';

const page = usePage();
const authUser = computed(() => page.props.auth.user);

const props = defineProps<{
    tasks: Array<{
        id: number;
        title: string;
        type: string;
        is_completed: boolean;
        image_path?: string;
        scheduled_at?: string;
        scheduled_time?: string;
        day_of_week?: number;
        day_of_month?: number;
    }>;
}>();

// Make tasks reactive for real-time updates
const localTasks = ref([...props.tasks]);

// Watch for prop changes in case of Inertia reloads
import { watch } from 'vue';
watch(() => props.tasks, (newTasks) => {
    localTasks.value = [...newTasks];
});

onMounted(() => {
    if (authUser.value) {
        // @ts-ignore
        window.Echo.private(`tasks.${authUser.value.id}`)
            .listen('TaskCreated', (e: any) => {
                localTasks.value.push(e.task);
            })
            .listen('TaskUpdated', (e: any) => {
                const index = localTasks.value.findIndex(t => t.id === e.task.id);
                if (index !== -1) {
                    localTasks.value[index] = e.task;
                }
            })
            .listen('TaskDeleted', (e: any) => {
                localTasks.value = localTasks.value.filter(t => t.id !== e.taskId);
            });
    }

    scheduleDailyReminder();
});

import { LocalNotifications } from '@capacitor/local-notifications';
import { Capacitor } from '@capacitor/core';

const scheduleDailyReminder = async () => {
    // Only run on native platforms (iOS/Android), not in web browser
    if (!Capacitor.isNativePlatform()) {
        return;
    }

    try {
        const perm = await LocalNotifications.requestPermissions();
        if (perm.display === 'granted') {
            // Schedule a daily notification for 9 AM
            // First cancel existing to avoid duplicates (naive approach)
            // Ideally we check if scheduled. 
            // For MVP: Schedule if not present.

            const pending = await LocalNotifications.getPending();
            if (pending.notifications.length === 0) {
                await LocalNotifications.schedule({
                    notifications: [
                        {
                            title: "Neatly",
                            body: "Time to check your daily cleaning rituals!",
                            id: 1,
                            schedule: {
                                on: { hour: 9, minute: 0 },
                                allowWhileIdle: true
                            },
                        }
                    ]
                });
            }
        }
    } catch (e) {
        console.log('Local Notifications not available', e);
    }
};



// Create Form
const createForm = useForm({
    title: '',
    type: 'daily',
    scheduled_at: '', // For custom
    scheduled_time: '',
    day_of_week: null as number | null,
    day_of_month: null as number | null,
    is_completed: false,
});

// Edit Form
const editForm = useForm({
    _method: 'PUT',
    id: null as number | null,
    title: '',
    type: '',
    scheduled_at: '',
    scheduled_time: '',
    day_of_week: null as number | null,
    day_of_month: null as number | null,
    is_completed: false,
    image: null as File | null,
});

const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);

const dailyTasks = computed(() => localTasks.value.filter(t => t.type === 'daily'));
const weeklyTasks = computed(() => localTasks.value.filter(t => t.type === 'weekly'));
const monthlyTasks = computed(() => localTasks.value.filter(t => t.type === 'monthly'));
const customTasks = computed(() => localTasks.value.filter(t => t.type === 'custom'));

// Helper to format 24h time to 12h
const formatTime = (time: string) => {
    if (!time) return '';
    const [hour, minute] = time.split(':');
    return new Date(0, 0, 0, +hour, +minute).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
};

const daysOfWeek = [
    { id: 1, name: 'Monday' },
    { id: 2, name: 'Tuesday' },
    { id: 3, name: 'Wednesday' },
    { id: 4, name: 'Thursday' },
    { id: 5, name: 'Friday' },
    { id: 6, name: 'Saturday' },
    { id: 7, name: 'Sunday' },
];

const daysOfMonth = Array.from({ length: 31 }, (_, i) => i + 1);

const getOrdinalSuffix = (n: number) => {
    const s = ["th", "st", "nd", "rd"];
    const v = n % 100;
    return s[(v - 20) % 10] || s[v] || s[0];
};

const toggleTask = (task: any) => {
    router.put(route('tasks.update', task.id), {
        is_completed: !task.is_completed
    }, {
        preserveScroll: true,
    });
};

const deleteTask = (task: any) => {
    if (confirm('Delete this task?')) {
        router.delete(route('tasks.destroy', task.id), { preserveScroll: true });
    }
};

const createTask = () => {
    createForm.post(route('tasks.store'), {
        onSuccess: () => {
            isCreateModalOpen.value = false;
            createForm.reset();
        }
    });
};

const openEditModal = (task: any) => {
    editForm.id = task.id;
    editForm.title = task.title;
    editForm.type = task.type;
    editForm.scheduled_at = task.scheduled_at ? task.scheduled_at.slice(0, 16) : '';
    // If these are not returned by the API yet, this might need adjustment, but assuming they are in the task object
    editForm.scheduled_time = task.scheduled_time ? task.scheduled_time.slice(0, 5) : '';
    editForm.day_of_week = task.day_of_week;
    editForm.day_of_month = task.day_of_month;
    editForm.is_completed = task.is_completed;
    editForm.image = null;
    isEditModalOpen.value = true;
};

const updateTask = () => {
    if (!editForm.id) return;
    editForm.post(route('tasks.update', editForm.id), {
        onSuccess: () => {
            isEditModalOpen.value = false;
            editForm.reset();
        }
    });
};
</script>

<template>

    <Head title="My Tasks" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Cleaning Schedule</h2>
                <PrimaryButton @click="isCreateModalOpen = true">
                    + New Task
                </PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <template v-for="(group, title) in {
                    'Daily Rituals': dailyTasks,
                    'Weekly Wins': weeklyTasks,
                    'Monthly Deep Clean': monthlyTasks,
                    'Custom Tasks': customTasks
                }" :key="title">
                    <section v-if="group.length">
                        <h3 class="text-lg font-bold text-gray-700 mb-3 px-4 sm:px-0">{{ title }}</h3>
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div v-for="task in group" :key="task.id"
                                class="p-4 border-b border-gray-100 flex items-center justify-between group transition-colors hover:bg-gray-50">
                                <div class="flex items-center gap-3 flex-1">
                                    <input type="checkbox" :checked="task.is_completed" @change="toggleTask(task)"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 h-5 w-5 cursor-pointer transform transition-transform active:scale-95" />

                                    <div class="flex-1 flex items-center justify-between mr-4"
                                        @click="openEditModal(task)">
                                        <div class="cursor-pointer">
                                            <span
                                                :class="{ 'line-through text-gray-400': task.is_completed, 'text-gray-800': !task.is_completed }"
                                                class="transition-all duration-200 block">
                                                {{ task.title }}
                                            </span>

                                            <!-- Subtitle: Scheduling Info -->
                                            <div class="text-xs text-slate-500 mt-1 flex gap-2">
                                                <span v-if="task.type === 'daily' && task.scheduled_time"
                                                    class="flex items-center gap-1">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    Daily at {{ formatTime(task.scheduled_time) }}
                                                </span>
                                                <span
                                                    v-if="task.type === 'weekly' && task.day_of_week && task.scheduled_time"
                                                    class="flex items-center gap-1">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                    {{daysOfWeek.find(d => d.id === task.day_of_week)?.name}}s at {{
                                                        formatTime(task.scheduled_time) }}
                                                </span>
                                                <span
                                                    v-if="task.type === 'monthly' && task.day_of_month && task.scheduled_time"
                                                    class="flex items-center gap-1">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                    {{ task.day_of_month === 31 ? 'End' : task.day_of_month +
                                                        getOrdinalSuffix(task.day_of_month) }} of month at {{
                                                        formatTime(task.scheduled_time) }}
                                                </span>
                                                <span v-if="task.type === 'custom' && task.scheduled_at"
                                                    class="flex items-center gap-1 text-orange-500">
                                                    {{ new Date(task.scheduled_at).toLocaleString([], {
                                                        dateStyle:
                                                            'short', timeStyle: 'short'
                                                    }) }}
                                                </span>
                                            </div>

                                            <span v-if="task.image_path"
                                                class="text-xs text-indigo-500 flex items-center gap-1 mt-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                Image Attached
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button @click="openEditModal(task)"
                                        class="text-gray-300 hover:text-indigo-500 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>
                                    <button @click="deleteTask(task)"
                                        class="text-gray-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>
                </template>

            </div>
        </div>

        <!-- Create Task Modal -->
        <Modal :show="isCreateModalOpen" @close="isCreateModalOpen = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Add New Task</h2>
                <div class="mt-4">
                    <InputLabel value="Title" />
                    <TextInput v-model="createForm.title" class="mt-1 block w-full" placeholder="e.g. Wash the car" />
                </div>
                <div class="mt-4">
                    <InputLabel value="Type" />
                    <select v-model="createForm.type"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="custom">Custom (One-time)</option>
                    </select>
                </div>

                <!-- Dynamic Inputs -->
                <div class="mt-4 grid grid-cols-2 gap-4">
                    <!-- Daily: Time Only -->
                    <div v-if="createForm.type === 'daily'" class="col-span-2">
                        <InputLabel value="What time?" />
                        <TextInput type="time" v-model="createForm.scheduled_time" class="mt-1 block w-full" />
                    </div>

                    <!-- Weekly: Day + Time -->
                    <div v-if="createForm.type === 'weekly'">
                        <InputLabel value="Which day?" />
                        <select v-model="createForm.day_of_week"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option v-for="day in daysOfWeek" :key="day.id" :value="day.id">{{ day.name }}</option>
                        </select>
                    </div>
                    <div v-if="createForm.type === 'weekly'">
                        <InputLabel value="What time?" />
                        <TextInput type="time" v-model="createForm.scheduled_time" class="mt-1 block w-full" />
                    </div>

                    <!-- Monthly: Date + Time -->
                    <div v-if="createForm.type === 'monthly'">
                        <InputLabel value="Day of Month" />
                        <select v-model="createForm.day_of_month"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option v-for="day in daysOfMonth" :key="day" :value="day">{{ day }}</option>
                        </select>
                        <p class="text-xs text-gray-400 mt-1">If month has fewer days, defaults to last day.</p>
                    </div>
                    <div v-if="createForm.type === 'monthly'">
                        <InputLabel value="What time?" />
                        <TextInput type="time" v-model="createForm.scheduled_time" class="mt-1 block w-full" />
                    </div>

                    <!-- Custom: Full Date -->
                    <div v-if="createForm.type === 'custom'" class="col-span-2">
                        <InputLabel value="When?" />
                        <TextInput type="datetime-local" v-model="createForm.scheduled_at" class="mt-1 block w-full" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="isCreateModalOpen = false">Cancel</SecondaryButton>
                    <PrimaryButton class="ms-3" :class="{ 'opacity-25': createForm.processing }"
                        :disabled="createForm.processing" @click="createTask">
                        Add Task
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Edit Task Modal -->
        <Modal :show="isEditModalOpen" @close="isEditModalOpen = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Edit Task</h2>

                <div class="mt-4" v-if="editForm.id">
                    <div v-if="props.tasks.find(t => t.id === editForm.id)?.image_path" class="mb-4">
                        <img :src="'/storage/' + props.tasks.find(t => t.id === editForm.id)?.image_path"
                            class="rounded-lg max-h-40 w-full object-cover" />
                    </div>
                </div>

                <div class="mt-4">
                    <InputLabel value="Title" />
                    <TextInput v-model="editForm.title" class="mt-1 block w-full" />
                </div>
                <div class="mt-4">
                    <InputLabel value="Type" />
                    <select v-model="editForm.type"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>

                <!-- Dynamic Inputs (Edit) -->
                <div class="mt-4 grid grid-cols-2 gap-4">
                    <!-- Daily: Time Only -->
                    <div v-if="editForm.type === 'daily'" class="col-span-2">
                        <InputLabel value="What time?" />
                        <TextInput type="time" v-model="editForm.scheduled_time" class="mt-1 block w-full" />
                    </div>

                    <!-- Weekly: Day + Time -->
                    <div v-if="editForm.type === 'weekly'">
                        <InputLabel value="Which day?" />
                        <select v-model="editForm.day_of_week"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option v-for="day in daysOfWeek" :key="day.id" :value="day.id">{{ day.name }}</option>
                        </select>
                    </div>
                    <div v-if="editForm.type === 'weekly'">
                        <InputLabel value="What time?" />
                        <TextInput type="time" v-model="editForm.scheduled_time" class="mt-1 block w-full" />
                    </div>

                    <!-- Monthly: Date + Time -->
                    <div v-if="editForm.type === 'monthly'">
                        <InputLabel value="Day of Month" />
                        <select v-model="editForm.day_of_month"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option v-for="day in daysOfMonth" :key="day" :value="day">{{ day }}</option>
                        </select>
                    </div>
                    <div v-if="editForm.type === 'monthly'">
                        <InputLabel value="What time?" />
                        <TextInput type="time" v-model="editForm.scheduled_time" class="mt-1 block w-full" />
                    </div>

                    <!-- Custom: Full Date -->
                    <div v-if="editForm.type === 'custom'" class="col-span-2">
                        <InputLabel value="When?" />
                        <TextInput type="datetime-local" v-model="editForm.scheduled_at" class="mt-1 block w-full" />
                    </div>
                </div>

                <div class="mt-4">
                    <InputLabel value="Upload Image" />
                    <input type="file" @input="editForm.image = ($event.target as HTMLInputElement).files?.[0] || null"
                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="isEditModalOpen = false">Cancel</SecondaryButton>
                    <PrimaryButton class="ms-3" :class="{ 'opacity-25': editForm.processing }"
                        :disabled="editForm.processing" @click="updateTask">
                        Save Changes
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
