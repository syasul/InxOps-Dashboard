<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <h1 class="text-2xl font-bold text-white uppercase tracking-tighter italic">Drive Explorer</h1>
                <p class="text-slate-500 text-sm">Hardware-integrated file system management</p>
            </div>
            <div class="flex items-center gap-4">
                <!-- Create Folder Button -->
                <button onclick="createNewFolder()" class="px-5 py-2.5 bg-white/5 border border-white/10 hover:bg-white/10 rounded-xl text-xs font-bold text-white uppercase tracking-widest transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                    New Folder
                </button>
                
                <div class="hidden md:flex px-4 py-2 bg-primary/10 border border-primary/20 rounded-xl items-center gap-2">
                    <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    <span class="text-xs font-black text-primary uppercase tracking-widest">DRAG & DROP READY</span>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6" id="drop-zone">
        <!-- Breadcrumbs -->
        <nav class="flex items-center gap-2 text-sm text-slate-500 font-medium bg-white/2 p-3 rounded-2xl border border-white/5 overflow-x-auto">
            @foreach($breadcrumbs as $breadcrumb)
                <a href="{{ route('storage.index', ['path' => $breadcrumb['path']]) }}" class="hover:text-primary transition-colors flex items-center gap-2 whitespace-nowrap">
                    @if($loop->first)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    @endif
                    {{ $breadcrumb['name'] }}
                </a>
                @if(!$loop->last)
                    <svg class="w-4 h-4 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                @endif
            @endforeach
        </nav>

        <!-- File Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
            <!-- Back Button if not root -->
            @if($path)
                @php
                    $parentPath = dirname($path);
                    if($parentPath === '.') $parentPath = '';
                @endphp
                <a href="{{ route('storage.index', ['path' => $parentPath]) }}" class="glass p-6 rounded-3xl border border-white/5 flex flex-col items-center justify-center gap-3 hover:bg-white/5 transition-all group cursor-pointer opacity-60">
                    <div class="w-16 h-16 rounded-2xl bg-slate-500/10 flex items-center justify-center text-slate-500 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                    </div>
                    <span class="text-xs font-bold text-white uppercase tracking-widest">Go Back</span>
                </a>
            @endif

            @foreach($items as $item)
                <div class="glass p-6 rounded-3xl border border-white/5 group relative overflow-hidden flex flex-col items-center gap-3">
                    <div class="absolute inset-0 bg-primary/5 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
                    
                    <!-- Action Buttons -->
                    <div class="absolute top-2 right-2 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity z-20">
                        @if($item['type'] === 'file')
                            <a href="{{ route('storage.download', ['path' => $item['path']]) }}" class="p-2 text-primary/50 hover:text-primary hover:bg-primary/10 rounded-lg transition-all" title="Download">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            </a>
                        @endif
                        <form action="{{ route('storage.destroy') }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="path" value="{{ $item['path'] }}">
                            <button type="submit" class="p-2 text-rose-500/50 hover:text-rose-500 hover:bg-rose-500/10 rounded-lg transition-all" onclick="return confirm('Delete this item?')">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>

                    @if($item['type'] === 'directory')
                        <a href="{{ route('storage.index', ['path' => $item['path']]) }}" class="flex flex-col items-center gap-3 w-full">
                            <div class="w-20 h-20 rounded-[2rem] bg-secondary/10 flex items-center justify-center text-secondary group-hover:scale-110 transition-transform duration-500 shadow-xl shadow-black/20">
                                <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>
                            </div>
                            <div class="text-center w-full">
                                <span class="block text-sm font-bold text-white truncate max-w-full px-2">{{ $item['name'] }}</span>
                                <span class="text-[10px] text-slate-500 font-bold uppercase tracking-tighter">Folder</span>
                            </div>
                        </a>
                    @else
                        <div class="flex flex-col items-center gap-3 w-full">
                            <div class="w-20 h-20 rounded-[2rem] bg-primary/10 flex items-center justify-center text-primary group-hover:scale-110 transition-transform duration-500 shadow-xl shadow-black/20">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            </div>
                            <div class="text-center w-full">
                                <span class="block text-sm font-bold text-white truncate max-w-full px-2" title="{{ $item['name'] }}">{{ $item['name'] }}</span>
                                <span class="text-[10px] text-slate-500 font-bold uppercase tracking-tighter">{{ $item['size'] }} • {{ strtoupper($item['extension']) }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach

            <!-- Empty State -->
            @if(empty($items))
                <div class="col-span-full py-32 flex flex-col items-center justify-center glass rounded-[3rem] border border-dashed border-white/10">
                    <div class="w-20 h-20 bg-white/5 rounded-full flex items-center justify-center mb-6 text-slate-600 animate-bounce">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-white uppercase tracking-widest">No Items Found</h3>
                    <p class="text-slate-500 text-sm mt-2 font-medium italic">Drop files here to start uploading</p>
                </div>
            @endif
        </div>

        <!-- Progress Overlay -->
        <div id="upload-overlay" class="fixed inset-0 bg-black/80 backdrop-blur-md z-[100] flex flex-col items-center justify-center opacity-0 pointer-events-none transition-opacity duration-300">
            <button onclick="document.getElementById('upload-overlay').classList.add('opacity-0', 'pointer-events-none')" class="absolute top-10 right-10 p-4 text-white/50 hover:text-white transition-colors">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            
            <div class="w-full max-w-md p-10 bg-surface rounded-[3rem] border border-white/10 space-y-6 text-center">
                <div class="relative w-32 h-32 mx-auto">
                    <div class="absolute inset-0 bg-primary/20 rounded-full animate-ping"></div>
                    <div class="relative w-full h-full bg-primary rounded-full flex items-center justify-center text-white">
                        <svg class="w-16 h-16 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    </div>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-white mb-2" id="upload-status">Ready to Upload</h2>
                    <div class="w-full bg-white/5 rounded-full h-2 overflow-hidden mb-4">
                        <div id="upload-progress-bar" class="bg-primary h-full rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                    <p class="text-slate-400 text-xs uppercase font-black tracking-widest" id="upload-percent">Drop files to start transmission</p>
                </div>
            </div>
        </div>

        <!-- Hidden New Folder Form -->
        <form id="new-folder-form" action="{{ route('storage.folder') }}" method="POST" class="hidden">
            @csrf
            <input type="hidden" name="path" value="{{ $path }}">
            <input type="hidden" name="name" id="new-folder-name">
        </form>
    </div>

    @push('scripts')
    <script>
        function createNewFolder() {
            const name = prompt("Enter folder name:");
            if (name && name.trim()) {
                const form = document.getElementById('new-folder-form');
                const input = document.getElementById('new-folder-name');
                input.value = name.trim();
                form.submit();
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const dropZone = document.getElementById('drop-zone');
            const overlay = document.getElementById('upload-overlay');
            const progressBar = document.getElementById('upload-progress-bar');
            const percentLabel = document.getElementById('upload-percent');
            const statusLabel = document.getElementById('upload-status');
            const currentPath = '{{ $path }}';

            let dragCounter = 0;

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                }, false);
            });

            window.addEventListener('dragenter', (e) => {
                dragCounter++;
                if (e.dataTransfer.types.includes('Files')) {
                    overlay.classList.remove('opacity-0', 'pointer-events-none');
                    overlay.classList.add('opacity-100', 'pointer-events-auto');
                }
            });

            window.addEventListener('dragleave', (e) => {
                dragCounter--;
                if (dragCounter === 0) {
                    overlay.classList.add('opacity-0', 'pointer-events-none');
                    overlay.classList.remove('opacity-100', 'pointer-events-auto');
                }
            });

            dropZone.addEventListener('drop', (e) => {
                dragCounter = 0;
                const dt = e.dataTransfer;
                if (dt.files.length > 0) {
                    handleUpload(dt.files);
                } else {
                    overlay.classList.add('opacity-0', 'pointer-events-none');
                }
            }, false);

            async function handleUpload(files) {
                statusLabel.innerText = 'Uploading Files...';
                overlay.classList.remove('opacity-0');
                overlay.classList.add('opacity-100', 'pointer-events-auto');
                
                const formData = new FormData();
                formData.append('path', currentPath);
                for (let i = 0; i < files.length; i++) {
                    formData.append('files[]', files[i]);
                }

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
                            statusLabel.innerText = 'Upload Successful!';
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            statusLabel.innerText = 'Upload Failed';
                            setTimeout(() => overlay.classList.add('opacity-0'), 2000);
                        }
                    };

                    xhr.send(formData);
                } catch (error) {
                    console.error('Upload error:', error);
                    overlay.classList.add('opacity-0');
                }
            }
        });
    </script>
    @endpush
</x-app-layout>
