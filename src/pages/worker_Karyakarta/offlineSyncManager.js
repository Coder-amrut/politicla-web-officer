/**
 * Offline Sync Manager for Karyakarta (Worker) Field Operations
 * Provides offline-first storage in localStorage, auto-detection of network
 * status, background queue synchronization when network is restored,
 * and caching of offline field data.
 */

const QUEUE_STORAGE_KEY = 'karyakarta_offline_queue';
const CACHE_STORAGE_KEY = 'karyakarta_offline_cache';

/**
 * Check if the browser currently has an active network connection
 */
export function isNetworkOnline() {
  return typeof navigator !== 'undefined' ? navigator.onLine : true;
}

/**
 * Get all queued offline submissions
 */
export function getOfflineQueue() {
  try {
    const raw = localStorage.getItem(QUEUE_STORAGE_KEY);
    return raw ? JSON.parse(raw) : [];
  } catch (e) {
    console.error('Failed to read offline queue:', e);
    return [];
  }
}

/**
 * Save offline queue and dispatch change event
 */
function saveOfflineQueue(queue) {
  try {
    localStorage.setItem(QUEUE_STORAGE_KEY, JSON.stringify(queue));
    if (typeof window !== 'undefined') {
      window.dispatchEvent(new CustomEvent('karyakarta:offline-queue-changed', {
        detail: { count: queue.length, queue }
      }));
    }
  } catch (e) {
    console.error('Failed to save offline queue:', e);
  }
}

/**
 * Add a new submission to the offline queue
 */
export function enqueueOfflineItem({
  type,
  label_mr,
  label_en,
  payload,
}) {
  const queue = getOfflineQueue();
  const newItem = {
    id: `sync_${Date.now()}_${Math.random().toString(36).substring(2, 8)}`,
    type,
    label_mr: label_mr || 'नवीन नोंद',
    label_en: label_en || 'New Submission',
    payload,
    createdAt: new Date().toISOString(),
    attempts: 0,
    lastError: null,
  };

  queue.push(newItem);
  saveOfflineQueue(queue);
  return newItem;
}

/**
 * Remove an item from the offline queue by ID
 */
export function removeQueueItem(id) {
  const queue = getOfflineQueue();
  const updated = queue.filter(item => item.id !== id);
  saveOfflineQueue(updated);
}

/**
 * Clear the entire offline queue
 */
export function clearOfflineQueue() {
  saveOfflineQueue([]);
}

/**
 * Cache offline field data for offline browsing
 */
export function cacheWorkerData(key, data) {
  try {
    const cache = getWorkerCache();
    cache[key] = {
      data,
      cachedAt: new Date().toISOString(),
    };
    localStorage.setItem(CACHE_STORAGE_KEY, JSON.stringify(cache));
  } catch (e) {
    console.warn('Failed to cache worker data:', e);
  }
}

/**
 * Retrieve cached worker data
 */
export function getWorkerCache(key = null) {
  try {
    const raw = localStorage.getItem(CACHE_STORAGE_KEY);
    const cache = raw ? JSON.parse(raw) : {};
    return key ? cache[key]?.data || null : cache;
  } catch (e) {
    return key ? null : {};
  }
}

/**
 * Synchronize all pending offline items to the database via workerApi
 */
export async function syncOfflineQueue(workerApi) {
  if (!isNetworkOnline()) {
    return {
      success: false,
      message: 'Network is offline. Cannot sync yet.',
      synced: 0,
      remaining: getOfflineQueue().length,
    };
  }

  const queue = getOfflineQueue();
  if (queue.length === 0) {
    return { success: true, synced: 0, remaining: 0 };
  }

  let syncedCount = 0;
  const remainingItems = [];

  for (const item of queue) {
    try {
      item.attempts = (item.attempts || 0) + 1;

      switch (item.type) {
        case 'SURVEY':
          await workerApi.submitSurvey(item.payload);
          syncedCount++;
          break;

        case 'ATTENDANCE_IN':
          await workerApi.punchIn(item.payload.photo, item.payload.locationData);
          syncedCount++;
          break;

        case 'ATTENDANCE_OUT':
          await workerApi.punchOut(item.payload.photo, item.payload.locationData);
          syncedCount++;
          break;

        case 'MEETING':
          await workerApi.scheduleMeeting(item.payload);
          syncedCount++;
          break;

        case 'COMPLAINT':
          await workerApi.storeComplaint(item.payload);
          syncedCount++;
          break;

        case 'COMPLAINT_STATUS':
          await workerApi.updateComplaintStatus(item.payload.id, item.payload.status);
          syncedCount++;
          break;

        case 'TASK':
          await workerApi.createTask(item.payload);
          syncedCount++;
          break;

        case 'FOLLOW_UP':
          await workerApi.createFollowUp(item.payload);
          syncedCount++;
          break;

        case 'VISIT_LOG':
          await workerApi.recordVisitLog(item.payload);
          syncedCount++;
          break;

        case 'EVM_VOTE':
          await workerApi.castEvmVote(item.payload);
          syncedCount++;
          break;

        default:
          console.warn('Unknown offline item type:', item.type);
          syncedCount++;
          break;
      }
    } catch (err) {
      console.error(`Failed to sync item [${item.id} - ${item.type}]:`, err);
      item.lastError = err?.response?.data?.message || err?.message || 'Network error';
      remainingItems.push(item);
      // If network failure, stop syncing the rest to preserve order
      if (!isNetworkOnline() || err?.code === 'ERR_NETWORK' || !err?.response) {
        const remainingUnprocessed = queue.slice(queue.indexOf(item) + 1);
        remainingItems.push(...remainingUnprocessed);
        break;
      }
    }
  }

  saveOfflineQueue(remainingItems);

  return {
    success: syncedCount > 0,
    synced: syncedCount,
    remaining: remainingItems.length,
  };
}
