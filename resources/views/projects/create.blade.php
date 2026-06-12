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

                <!-- Directory Selection -->
                <div class="space-y-2">
                    <label class="block text-xs font-black uppercase tracking-[0.2em] text-slate-500 mb-3 ml-1">Directory Path on Server</label>
                    <select name="directory_path" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-white focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all outline-none appearance-none cursor-pointer" required>
                        <option value="" disabled selected class="bg-[#0c0e14]">Select existing directory...</option>
                        @foreach($availableFolders as $folder)
                            <option value="{{ $folder['full_path'] }}" class="bg-[#0c0e14]">{{ $folder['name'] }} ({{ $folder['full_path'] }})</option>
                        @endforeach
                        <option value="custom" class="bg-[#0c0e14] opacity-50">+ Add Custom Path...</option>
                    </select>
                    <p class="text-[10px] text-slate-600 font-bold uppercase tracking-widest mt-2 ml-1">Auto-detected projects from /webapps</p>
                </div>

                <!-- Hidden Custom Path Input (Optional) -->
                <div id="custom-path-container" class="hidden space-y-2">
                    <input type="text" id="custom_path" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-white focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all outline-none text-sm font-mono" placeholder="/absolute/path/to/project">
                </div>

                <div class="pt-4 flex gap-4">
                    <button type="submit" class="btn-primary flex-1">Create Project</button>
                    <a href="{{ route('projects.index') }}" class="px-6 py-3 bg-white/5 hover:bg-white/10 text-white rounded-xl transition-colors">Cancel</a>
                </div>
            </div>
        </form>
    </div>
    <script>
        document.querySelector('select[name="directory_path"]').addEventListener('change', function() {
            const customPathContainer = document.getElementById('custom-path-container');
            const customInput = document.getElementById('custom_path');
            
            if (this.value === 'custom') {
                customPathContainer.classList.remove('hidden');
                customInput.setAttribute('name', 'directory_path');
                this.removeAttribute('name');
                customInput.focus();
            } else {
                customPathContainer.classList.add('hidden');
                customInput.removeAttribute('name');
                this.setAttribute('name', 'directory_path');
            }
        });
    </script>
</x-app-layout>
