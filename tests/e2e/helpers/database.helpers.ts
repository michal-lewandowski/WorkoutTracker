import { promisify } from 'util';
import { exec } from 'child_process';

const execAsync = promisify(exec);

export async function resetDatabase() {
    await execAsync('bash .docker/scripts/reset-local-db.sh');
}
