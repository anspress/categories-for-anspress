<?php
	global $question_categories;
?>
<?php if(ap_opt('enable_categories')): ?>
<div id="ap-categories" class="clearfix">
	<ul class="ap-term-category-box nav">
		<?php foreach($question_categories as $key => $category) : ?>
			<li>
				<div class="ap-term-list-inner clearfix">
					<div class="ap-term-info">
						<span class="term-post-count"><?php echo $category->count; ?> Questions</span>
						<a class="term-title" href="<?php echo get_category_link( $category );?>">
							<span><?php echo $category->name; ?></span>
						</a>					
						
						<?php if(strlen($category->description) > 0) : ?>
							<div class="term-detail" data-action="ap_fold_content">					
								<div class="term-description ap-fold-inner"><?php echo $category->description; ?></div>							
							</div>
						<?php endif; ?>
						<i data-button="ap_expand_toggle" class="ap-expand-content <?php echo ap_icon('more') ?>"></i>
						<a class="ap-btn ap-btn-transparent ap-view-all-btn block" href="<?php echo get_category_link( $category );?>"><?php _e('All questions', 'ap') ?></a>
					</div>
					<div class="ap-term-questions-list">
						<?php
							$question_args = array();
							$question_args['showposts'] = 5;
							$question_args['tax_query'] = array(
								array(
									'taxonomy' => 'question_category',
									'field'    => 'term_id',
									'terms'    => $category->term_id,
								),
							);
							$questions = new Question_Query( $question_args );
								include ap_get_theme_location('categories-question-list.php', CATEGORIES_FOR_ANSPRESS_DIR);
							wp_reset_postdata();
						?>
					</div>
				</div>
				<?php
					$sub_cat_count = count(get_term_children( $category->term_id, 'question_category' ));
					
					if($sub_cat_count >0){
						echo '<div class="ap-term-sub">';
						echo '<div class="sub-cat-count">' .$sub_cat_count.' '.__('Sub Categories', 'ap') .'</div>';
						
						ap_child_cat_list($category->term_id);
						echo '</div>';
					}
				?>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
<?php ap_pagination(); ?>
<?php else: ?>
	<div class="ap-tax-disabled">
		<?php _e('Categories are disabled', 'ap'); ?>
	</div>
<?php endif; ?>