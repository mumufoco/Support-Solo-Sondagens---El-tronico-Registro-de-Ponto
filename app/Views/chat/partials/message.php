<?php
$isOwn = $message->sender_id === $employee['id'];
?>

<div class="d-flex mb-3 <?= $isOwn ? 'justify-content-end' : '' ?>" data-message-id="<?= $message->id ?>">
    <div class="message-bubble <?= $isOwn ? 'message-own bg-primary text-white' : 'bg-light' ?> p-3 rounded shadow-sm" style="max-width: 70%;">
        <?php if (!$isOwn): ?>
            <div class="small fw-bold mb-1"><?= esc($message->sender_name) ?></div>
        <?php endif; ?>

        <?php if ($message->reply_to && $message->reply_message): ?>
            <div class="alert alert-secondary py-1 px-2 mb-2" style="opacity: 0.8; font-size: 12px;">
                <i class="fas fa-reply"></i>
                <strong><?= esc($message->reply_sender_name) ?>:</strong>
                <?= esc(substr($message->reply_message, 0, 100)) ?><?= strlen($message->reply_message) > 100 ? '...' : '' ?>
            </div>
        <?php endif; ?>

        <div class="message-text">
            <?= nl2br(esc($message->message)) ?>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-2">
            <div class="message-time text-<?= $isOwn ? 'white' : 'muted' ?>" style="font-size: 11px;">
                <?= format_time($message->created_at) ?>
                <?php if ($message->edited_at): ?>
                    <i class="fas fa-pen" title="Editada"></i>
                <?php endif; ?>
            </div>

            <div class="message-actions">
                <?php if (isset($message->reactions) && is_array($message->reactions) && count($message->reactions) > 0): ?>
                    <span class="reactions">
                        <?php foreach ($message->reactions as $emoji => $count): ?>
                            <span class="badge bg-light text-dark me-1" onclick="chat.addReaction(<?= $message->id ?>, '<?= esc($emoji) ?>')">
                                <?= $emoji ?> <?= $count ?>
                            </span>
                        <?php endforeach; ?>
                    </span>
                <?php endif; ?>

                <div class="dropdown d-inline">
                    <button class="btn btn-sm btn-link text-<?= $isOwn ? 'white' : 'dark' ?> p-0 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="#" onclick="replyToMessage(<?= $message->id ?>, '<?= addslashes(esc($message->message)) ?>'); return false;">
                                <i class="fas fa-reply"></i> Responder
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" onclick="showReactionPicker(<?= $message->id ?>); return false;">
                                <i class="far fa-smile"></i> Reagir
                            </a>
                        </li>
                        <?php if ($isOwn): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="editMessage(<?= $message->id ?>, '<?= addslashes(esc($message->message)) ?>'); return false;">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item text-danger" href="#" onclick="deleteMessage(<?= $message->id ?>); return false;">
                                    <i class="fas fa-trash"></i> Excluir
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
