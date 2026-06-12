<x-app-layout>
    <x-slot name="header">Register New Project</x-slot>

    <div class="max-w-2xl">
        <form action="{{ route('projects.store') }}" method="POST" class="space-y-6">
            @csrf
            
            <div class="glass p-8 rounded-2xl space-y-6">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-2">Project Name</label>
                    <input type="text" name="name" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-primary focus:ring-0 transition-colors" placeholder="My Awesome App" required>
                </div>

                <!-- Repo URL -->
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-2">GitHub Repository URL</label>
                    <input type="url" name="repo_url" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-primary focus:ring-0 transition-colors" placeholder="https://github.com/user/repo" required>
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <!-- Branch -->
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-2">Branch</label>
                        <input type="text" name="branch" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-primary focus:ring-0 transition-colors" value="main" required>
                    </div>
                    <!-- Port -->
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-2">Port (optional)</label>
                        <input type="number" name="port" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-primary focus:ring-0 transition-colors" placeholder="8000">
                    </div>
                </div>

                <!-- Directory Path -->
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-2">Directory Path on Server</label>
                    <input type="text" name="directory_path" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-primary focus:ring-0 transition-colors" placeholder="/var/www/my-app" required>
                    <p class="mt-2 text-xs text-slate-500">The absolute path where project will be cloned/deployed.</p>
                </div>

                <div class="pt-4 flex gap-4">
                    <button type="submit" class="btn-primary flex-1">Create Project</button>
                    <a href="{{ route('projects.index') }}" class="px-6 py-3 bg-white/5 hover:bg-white/10 text-white rounded-xl transition-colors">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
