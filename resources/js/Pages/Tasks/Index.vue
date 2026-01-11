<script setup lang="ts">
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

const scheduleDailyReminder = async () => {
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
                            title: "Cleanly",
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
        console.log('Local Notifications not available (web mode?)', e);
    }
};



const createForm = useForm({
    title: '',
    type: 'daily',
    is_completed: false,
});

const editForm = useForm({
    _method: 'PUT',
    id: null as number | null,
    title: '',
    type: '',
    is_completed: false,
    image: null as File | null,
});

const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);

const dailyTasks = computed(() => localTasks.value.filter(t => t.type === 'daily'));
const weeklyTasks = computed(() => localTasks.value.filter(t => t.type === 'weekly'));
const monthlyTasks = computed(() => localTasks.value.filter(t => t.type === 'monthly'));
const customTasks = computed(() => localTasks.value.filter(t => t.type === 'custom'));

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
    editForm.is_completed = task.is_completed;
    editForm.image = null;
    isEditModalOpen.value = true;
};

const updateTask = () => {
    if (!editForm.id) return;
    
    // Use POST with _method=PUT for file uploads
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
                                    
                                    <div class="flex-1 flex items-center justify-between mr-4" @click="openEditModal(task)">
                                        <div class="cursor-pointer">
                                            <span :class="{'line-through text-gray-400': task.is_completed, 'text-gray-800': !task.is_completed}" 
                                                  class="transition-all duration-200 block">
                                                {{ task.title }}
                                            </span>
                                            <span v-if="task.image_path" class="text-xs text-indigo-500 flex items-center gap-1 mt-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                Image Attached
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                     <button @click="openEditModal(task)" class="text-gray-300 hover:text-indigo-500 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>
                                    <button @click="deleteTask(task)" class="text-gray-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
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
                    <select v-model="createForm.type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>
                 <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="isCreateModalOpen = false">Cancel</SecondaryButton>
                    <PrimaryButton class="ms-3" :class="{ 'opacity-25': createForm.processing }" :disabled="createForm.processing" @click="createTask">
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
                        <img :src="'/storage/' + props.tasks.find(t => t.id === editForm.id)?.image_path" class="rounded-lg max-h-40 w-full object-cover" />
                    </div>
                </div>

                <div class="mt-4">
                    <InputLabel value="Title" />
                    <TextInput v-model="editForm.title" class="mt-1 block w-full" />
                </div>
                <div class="mt-4">
                    <InputLabel value="Type" />
                    <select v-model="editForm.type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>
                 <div class="mt-4">
                    <InputLabel value="Upload Image" />
                    <input type="file" @input="editForm.image = ($event.target as HTMLInputElement).files?.[0] || null" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"/>
                </div>

                 <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="isEditModalOpen = false">Cancel</SecondaryButton>
                    <PrimaryButton class="ms-3" :class="{ 'opacity-25': editForm.processing }" :disabled="editForm.processing" @click="updateTask">
                        Save Changes
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
