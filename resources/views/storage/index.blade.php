<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 w-full">
            <div>
                <h1 class="text-3xl font-black text-white uppercase tracking-tighter italic leading-none mb-1">Drive Explorer</h1>
                <p class="text-slate-500 text-sm font-medium">Remote Cloud Storage & File System</p>
            </div>
            
            <div class="flex items-center gap-3">
                <!-- Action Buttons -->
                <button onclick="document.getElementById('file-input').click()" class="px-6 py-3 bg-primary hover:bg-primary/90 rounded-2xl text-xs font-black text-white uppercase tracking-widest transition-all flex items-center gap-2 shadow-lg shadow-primary/20 group">
                    <svg class="w-4 h-4 group-hover:bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    Upload Files
                </button>
                
                <button onclick="createNewFolder()" class="px-6 py-3 bg-white/5 border border-white/10 hover:bg-white/10 rounded-2xl text-xs font-black text-white uppercase tracking-widest transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                    New Folder
                </button>

                <input type="file" id="file-input" class="hidden" multiple onchange="handleManualUpload(this.files)">
            </div>
        </div>
    </x-slot>

    <div class="space-y-8" id="drop-zone-container">
        <!-- Explorer Header Card -->
        <div class="glass p-4 rounded-[2rem] border border-white/10 flex items-center justify-between overflow-hidden relative">
            <div class="absolute inset-0 bg-gradient-to-r from-primary/5 via-transparent to-transparent"></div>
            <nav class="flex items-center gap-2 text-xs text-slate-400 font-bold bg-black/20 px-6 py-3 rounded-xl border border-white/5 overflow-x-auto relative">
                @foreach($breadcrumbs as $breadcrumb)
                    <a href="{{ route('storage.index', ['path' => $breadcrumb['path']]) }}" class="hover:text-primary transition-colors flex items-center gap-2 whitespace-nowrap">
                        @if($loop->first)
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        @endif
                        {{ strtoupper($breadcrumb['name']) }}
                    </a>
                    @if(!$loop->last)
                        <svg class="w-3 h-3 text-slate-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    @endif
                @endforeach
            </nav>
            <div class="mr-4 hidden md:flex items-center gap-6">
                 <div class="text-right">
                    <p class="text-[9px] font-black text-slate-600 uppercase tracking-widest">Storage Status</p>
                    <p class="text-[11px] font-bold text-emerald-500 uppercase">Synchronized</p>
                 </div>
            </div>
        </div>

        <!-- File Content -->
        @if(count($items) > 0 || $path)
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
            <!-- Back Button -->
            @if($path)
                @php $parentPath = dirname($path); if($parentPath === '.') $parentPath = ''; @endphp
                <a href="{{ route('storage.index', ['path' => $parentPath]) }}" class="glass p-8 rounded-[2.5rem] border border-white/5 flex flex-col items-center justify-center gap-4 hover:bg-white/5 transition-all group cursor-pointer opacity-60">
                    <div class="w-20 h-20 rounded-3xl bg-slate-500/10 flex items-center justify-center text-slate-500 group-hover:scale-110 transition-transform">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                    </div>
                    <span class="text-[10px] font-black text-white uppercase tracking-widest">Navigate Up</span>
                </a>
            @endif

            @foreach($items as $item)
                <div class="glass p-8 rounded-[2.5rem] border border-white/5 group relative overflow-hidden flex flex-col items-center gap-4 hover:border-primary/30 transition-all transition-duration-500">
                    <div class="absolute inset-0 bg-gradient-to-b from-primary/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
                    
                    <!-- Top Ribbon for files -->
                    @if($item['type'] === 'file')
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-transparent via-primary/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="absolute top-4 right-4 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-all transform translate-y-2 group-hover:translate-y-0 z-20">
                        @if($item['type'] === 'file')
                            <a href="{{ route('storage.download', ['path' => $item['path']]) }}" class="p-2.5 bg-black/40 text-primary hover:bg-primary hover:text-white rounded-xl transition-all shadow-xl" title="Download">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            </a>
                        @endif
                        <form action="{{ route('storage.destroy') }}" method="POST">
                            @csrf @method('DELETE')
                            <input type="hidden" name="path" value="{{ $item['path'] }}">
                            <button type="submit" class="p-2.5 bg-black/40 text-rose-500 hover:bg-rose-500 hover:text-white rounded-xl transition-all shadow-xl" onclick="return confirm('Secure Delete?')">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>

                    @if($item['type'] === 'directory')
                        <a href="{{ route('storage.index', ['path' => $item['path']]) }}" class="flex flex-col items-center gap-4 w-full">
                            <div class="relative">
                                <div class="w-24 h-24 rounded-[2.5rem] bg-secondary/10 flex items-center justify-center text-secondary group-hover:scale-110 transition-transform duration-500 shadow-2xl shadow-secondary/5 group-hover:shadow-secondary/20">
                                    <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>
                                </div>
                            </div>
                            <div class="text-center w-full px-2">
                                <span class="block text-sm font-black text-white truncate mb-1">{{ $item['name'] }}</span>
                                <span class="text-[9px] text-slate-500 font-black uppercase tracking-[0.2em] bg-white/5 py-1 px-3 rounded-full">Directory</span>
                            </div>
                        </a>
                    @else
                        <div class="flex flex-col items-center gap-4 w-full">
                            <div class="w-24 h-24 rounded-[2.5rem] bg-primary/10 flex items-center justify-center text-primary group-hover:scale-110 transition-transform duration-500 shadow-2xl shadow-primary/5 group-hover:shadow-primary/20">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            </div>
                            <div class="text-center w-full px-2">
                                <span class="block text-sm font-black text-white truncate mb-1" title="{{ $item['name'] }}">{{ $item['name'] }}</span>
                                <span class="text-[9px] text-slate-500 font-black uppercase tracking-[0.2em] bg-white/5 py-1 px-3 rounded-full">{{ $item['size'] }} • {{ strtoupper($item['extension']) }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        @else
        <!-- Empty State Hero -->
        <div onclick="document.getElementById('file-input').click()" class="relative border-2 border-dashed border-white/5 rounded-[4rem] p-24 text-center group cursor-pointer hover:border-primary/40 transition-all overflow-hidden">
            <div class="absolute inset-0 bg-primary/2 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <!-- Background Glow -->
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-primary/10 blur-[120px] pointer-events-none"></div>

            <div class="relative z-10 space-y-8">
                <div class="w-32 h-32 mx-auto rounded-[3rem] bg-white/5 border border-white/5 flex items-center justify-center text-slate-600 group-hover:scale-110 group-hover:bg-primary/10 group-hover:text-primary transition-all duration-700 shadow-2xl">
                    <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                </div>
                <div class="max-w-md mx-auto">
                    <h2 class="text-3xl font-black text-white uppercase tracking-tighter mb-4 italic">No Files Detected</h2>
                    <p class="text-slate-500 font-medium leading-relaxed">Your cloud storage is currently empty. Drop your first mission-critical data here or click to browse local files.</p>
                </div>
                <button class="px-8 py-4 bg-primary text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl shadow-primary/30 hover:scale-105 transition-transform">
                    Start Initial Transmission
                </button>
            </div>
        </div>
        @endif

        <!-- Global Progress Overlay -->
        <div id="upload-overlay" class="fixed inset-0 bg-black/90 backdrop-blur-2xl z-[100] flex flex-col items-center justify-center opacity-0 pointer-events-none transition-all duration-500">
            <button onclick="closeOverlay()" class="absolute top-12 right-12 p-3 bg-white/5 rounded-2xl text-white/50 hover:text-white transition-all border border-white/5">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            
            <div class="w-full max-w-lg p-12 bg-surface rounded-[4rem] border border-white/10 space-y-10 text-center relative overflow-hidden">
                <div class="absolute inset-0 bg-primary/5"></div>
                <div class="relative z-10 space-y-8">
                    <div class="relative w-40 h-40 mx-auto">
                        <div class="absolute inset-0 bg-primary/30 rounded-[3rem] animate-ping"></div>
                        <div class="relative w-full h-full bg-primary rounded-[3rem] shadow-2xl shadow-primary/50 flex items-center justify-center text-white">
                            <svg class="w-20 h-20 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        </div>
                    </div>
                    <div>
                        <h2 class="text-3xl font-black text-white uppercase tracking-tighter mb-2 italic" id="upload-status">Ready to Transmit</h2>
                        <div class="w-full bg-white/5 rounded-full h-3 overflow-hidden shadow-inner p-0.5 border border-white/5 mb-6">
                            <div id="upload-progress-bar" class="bg-gradient-to-r from-primary to-secondary h-full rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                        <p class="text-slate-400 text-[10px] font-black uppercase tracking-[0.4em]" id="upload-percent">0% COMPLETED</p>
                    </div>
                </div>
            </div>
        </div>

        <form id="new-folder-form" action="{{ route('storage.folder') }}" method="POST" class="hidden">
            @csrf <input type="hidden" name="path" value="{{ $path }}">
            <input type="hidden" name="name" id="new-folder-name">
        </form>
    </div>

    @push('scripts')
    <script>
        function createNewFolder() {
            const name = prompt("Enter folder name:");
            if (name && name.trim()) {
                document.getElementById('new-folder-name').value = name.trim();
                document.getElementById('new-folder-form').submit();
            }
        }

        function handleManualUpload(files) {
            if (files.length > 0) window.initiateUpload(files);
        }

        function closeOverlay() {
            const overlay = document.getElementById('upload-overlay');
            overlay.classList.add('opacity-0', 'pointer-events-none');
            overlay.classList.remove('opacity-100', 'pointer-events-auto');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const dropZone = document.getElementById('drop-zone-container');
            const overlay = document.getElementById('upload-overlay');
            const progressBar = document.getElementById('upload-progress-bar');
            const percentLabel = document.getElementById('upload-percent');
            const statusLabel = document.getElementById('upload-status');
            let dragCounter = 0;

            window.initiateUpload = async function(files) {
                overlay.classList.remove('opacity-0', 'pointer-events-none');
                overlay.classList.add('opacity-100', 'pointer-events-auto');
                statusLabel.innerText = 'Transmitting Data...';
                
                const formData = new FormData();
                formData.append('path', '{{ $path }}');
                for (let i = 0; i < files.length; i++) formData.append('files[]', files[i]);

                try {
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', '{{ route("storage.upload") }}', true);
                    xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');

                    xhr.upload.onprogress = (e) => {
                        if (e.lengthComputable) {
                            const percent = Math.round((e.loaded / e.total) * 100);
                            progressBar.style.width = percent + '%';
                            percentLabel.innerText = percent + '% COMPLETED';
                        }
                    };

                    xhr.onload = () => {
                        if (xhr.status === 200) {
                            statusLabel.innerText = 'Transmission Successful!';
                            setTimeout(() => window.location.reload(), 800);
                        } else {
                            statusLabel.innerText = 'Transmission Failed';
                            setTimeout(closeOverlay, 1500);
                        }
                    };
                    xhr.send(formData);
                } catch (error) {
                    console.error('Transmission error:', error);
                    closeOverlay();
                }
            };

            window.addEventListener('dragenter', (e) => {
                dragCounter++;
                if (e.dataTransfer.types.includes('Files')) {
                    overlay.classList.remove('opacity-0', 'pointer-events-none');
                    overlay.classList.add('opacity-100', 'pointer-events-auto');
                }
            });

            window.addEventListener('dragleave', (e) => {
                dragCounter--;
                if (dragCounter === 0) closeOverlay();
            });

            window.addEventListener('drop', (e) => {
                e.preventDefault();
                dragCounter = 0;
                if (e.dataTransfer.files.length > 0) window.initiateUpload(e.dataTransfer.files);
                else closeOverlay();
            });

            window.addEventListener('dragover', e => e.preventDefault());
        });
    </script>
    @endpush
</x-app-layout>
