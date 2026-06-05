<?php function commentCard(CommentContent $comment, ?User $currentUser = null) { ?>
    <?php
        $canDelete = $currentUser !== null && $currentUser->getUserID() === $comment->getUserId();
        $deleteLink = Common::createLinkToSitePage('api.php/deletecomment', [
            'comment_id' => $comment->getCommentId(),
            'video_id' => $comment->getVideoId()
        ]);
    ?>

    <article class="comment-card" id="comment-<?= View::e($comment->getCommentId()) ?>">
        <img
            class="comment-avatar"
            src="<?= View::e($comment->getUserImage()) ?>"
            alt="<?= View::e($comment->getUserName()) ?>"
        >

        <div class="comment-main">
            <div class="comment-topline">
                <div class="comment-meta">
                    <span class="comment-author">
                        <?= View::e($comment->getUserName()) ?>
                    </span>
                    <span class="comment-date">
                        <i class="bi bi-clock"></i>
                        <?= View::e($comment->getPostedAt()) ?>
                    </span>
                </div>

                <?php if ($canDelete) { ?>
                    <a
                        class="comment-delete-btn"
                        href="<?= $deleteLink ?>"
                        aria-label="Delete comment"
                        title="Delete comment"
                        onclick="return confirm('Delete this comment?');"
                    >
                        <i class="bi bi-trash"></i>
                    </a>
                <?php } ?>
            </div>

            <p class="comment-body">
                <?= nl2br(View::e($comment->getBody())) ?>
            </p>

            <div class="comment-actions">
                <button
                    class="comment-reply-btn"
                    type="button"
                    data-comment-reply-toggle
                    data-reply-target="comment-reply-<?= View::e($comment->getCommentId()) ?>"
                    aria-expanded="false"
                    aria-controls="comment-reply-<?= View::e($comment->getCommentId()) ?>"
                >
                    <i class="bi bi-reply"></i>
                    Reply
                </button>
            </div>

            <div
                class="comment-reply-slot"
                id="comment-reply-<?= View::e($comment->getCommentId()) ?>"
                hidden
            >
                <?= addCommentBar($comment->getVideoId(), $currentUser, $comment->getCommentId()) ?>
            </div>

            <?php if (count($comment->getChildren()) > 0) { ?>
                <div class="comment-children">
                    <?php
                        foreach ($comment->getChildren() as $childComment) {
                            echo commentCard($childComment, $currentUser);
                        }
                    ?>
                </div>
            <?php } ?>
        </div>
    </article>
<?php } ?>
