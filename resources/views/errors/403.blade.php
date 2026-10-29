<x-error-page code="403" title="Akses ditolak" :message="$exception->getMessage() ?: 'Anda tidak memiliki akses ke halaman ini.'" />
